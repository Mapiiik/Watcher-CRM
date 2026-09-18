<?php
declare(strict_types=1);

namespace App\Controller;

use App\Documents\PrintedDocument;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\Customer;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\DocumentVariant;
use App\Proposals\DrawnPaper;
use App\Proposals\ProposalPapers;
use App\Proposals\RoundOfPapers;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use RuntimeException;
use Throwable;

/**
 * Documents Controller
 *
 * Where the papers of a customer and of their contracts are worked on. The same table of documents
 * used to be drawn on six pages and the printing on two more, each with its own navigation and its
 * own half of the answer to "what became of the papers" - so the table stayed and the pages around
 * it became this one.
 *
 * Two pages and one handing over. The workbench is where somebody stands while they deal with a
 * customer; the listing answers what state things are in and is what an overview would point at;
 * and generating is not a page at all, only the paper itself.
 *
 * No table of its own: what it shows belongs to the two kinds of round, and it reads them.
 */
class DocumentsController extends AppController
{
    /**
     * Which agendas a round may belong to, by what the address calls them.
     *
     * @var array<string>
     */
    private const AGENDAS = ['CustomerProposals', 'ContractProposals'];

    /**
     * Every round in view, listed rather than worked on.
     *
     * The counterpart of the workbench and deliberately thinner: one row to a round, saying what
     * state it is in and nothing about the papers themselves. The nesting narrows it exactly as it
     * narrows every other listing.
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $show_settled = toBool($this->getRequest()->getQuery('show_settled')) ?? false;
        $search = trim((string)$this->getRequest()->getQuery('search'));

        $proposals = $this->roundsInView(finder: $show_settled ? 'all' : 'open');

        if ($search !== '') {
            $proposals->where($this->whatIsBeingLookedFor($search));
        }

        $page = $this->paginate($proposals, [
            'sortableFields' => [
                'CustomerProposals.effective_from',
                'CustomerProposals.sent_date',
                'CustomerProposals.conclusion_date',
            ],
        ]);

        // The rows the table reads, and beside them the page they were taken from - the pager
        // draws itself from whichever view variable holds one.
        $this->set('rounds', $this->asRows($page));
        $this->set('paginated', $page);
        $this->set('show_settled', $show_settled);
        $this->set('showCustomer', $this->customer_id === null);
    }

    /**
     * The papers of whoever the page is nested under, and everything that may be done with them.
     *
     * @return \Cake\Http\Response|null Renders view, or sends the caller where the page exists.
     */
    public function manage(): ?Response
    {
        $round = $this->roundAsked();
        $version = $this->versionAsked();

        // Asked first, because the papers say whose they are: a link from a page that stands
        // under nobody - an overview, the dashboard - only has to say which papers it means.
        $sentOn = $this->whereTheRoundIsWorkedOn($round, $version);

        if ($sentOn !== null) {
            return $sentOn;
        }

        if ($this->customer_id === null) {
            // There is no managing the papers of everybody at once, and the listing is what a page
            // asked for without a customer was really after.
            return $this->redirect(['action' => 'index']);
        }

        $customers = $this->fetchTable('Customers');
        $customer = $customers->get($this->customer_id, contain: [
            'AccountingProfiles',
            'Addresses' => ['Countries'],
            'Emails',
            'Phones',
            'Creators',
            'Modifiers',
        ]);

        $contract = null;
        if ($this->contract_id !== null) {
            $contract = $this->fetchTable('Contracts')->get($this->contract_id, contain: [
                'Commissions',
                'ContractStates',
                'Customers',
                'InstallationAddresses',
                'InstallationTechnicians',
                'ServiceTypes',
                'UninstallationTechnicians',
                'Creators',
                'Modifiers',
            ]);
        }

        $this->set('customer', $customer);
        $this->set('contract', $contract);
        $this->set('version', $version);
        $this->set('round', $round);
        $this->set('rounds', $this->asRows($this->whatIsStillWorkedOn($this->roundsInView($version), $round)));
        $this->set('scope', $this->scopeOfTheTable($round, $version));
        $this->set('about', $this->whatTheTableIsOf($round, $version, $contract, $customer));
        // The papers of the contracts go out in the same envelope, so they are in view unless the
        // page was asked to leave them out.
        $this->set('with_contracts', toBool($this->getRequest()->getQuery('with_contracts')) ?? true);
        $this->set('show_revoked', $this->alsoWhatWasGivenUpOn());
        $this->set('showCustomer', false);

        return null;
    }

    /**
     * Hands over one paper, drawing it if it has never been drawn.
     *
     * Not a page: the links that lead here open a window of their own, and what comes back is the
     * document. Asked for without the extension it only sends the caller back to where the link
     * was, which is what happens when something is wrong with the request itself.
     *
     * @return \Cake\Http\Response|null The paper, or a redirect back to the workbench.
     */
    public function generate(): ?Response
    {
        $round = $this->roundAsked();
        $document_type = (string)$this->getRequest()->getQuery('document_type');
        $signed = toBool($this->getRequest()->getQuery('signed')) ?? false;

        if ($round === null || $document_type === '') {
            $this->Flash->error(__('Invalid type of document.'));

            return $this->redirect(['action' => 'manage']);
        }

        try {
            return $this->handOver((new DrawnPaper())->of($round, $document_type, $signed));
        } catch (RuntimeException $stopped) {
            $this->Flash->error($stopped->getMessage());

            return $this->redirect(['action' => 'manage', '?' => ['proposal_id' => $round->id]]);
        }
    }

    /**
     * Files what came back, a document at a time.
     *
     * What is offered is what the page it was opened from is about: on a proposal put to the
     * customer, everything the whole package may have; on the papers of one contract, only theirs.
     * Wider than what was drawn here either way - a scan comes back for whatever was signed,
     * wherever the paper was printed.
     *
     * @return \Cake\Http\Response|null Redirects when filed, renders the form otherwise.
     */
    public function addPages(): ?Response
    {
        $round = $this->roundAsked();

        if ($round === null) {
            return $this->redirect(['action' => 'manage']);
        }

        $printed = $this->whatMayComeBackFor($round);

        if ($this->request->is(['patch', 'post', 'put'])) {
            // Which paper of which record, said in one field: the scan is of one document and the
            // package holds several records that have one.
            [$holder, $document_type] = array_pad(
                explode('/', (string)$this->getRequest()->getData('document_type'), 2),
                2,
                '',
            );
            $variant = DocumentVariant::tryFrom((string)$this->getRequest()->getData('variant'));
            $files = $this->getRequest()->getUploadedFiles()['papers'] ?? [];
            $model = $printed[$holder]['model'] ?? null;

            if ($variant === null || $document_type === '' || $model === null || !is_array($files)) {
                $this->Flash->error(__('It was not said what those pages are.'));

                return null;
            }

            try {
                $filed = (new ProposalPapers())->take(
                    $model,
                    $holder,
                    $document_type,
                    $variant,
                    array_values($files),
                );

                // Said whether or not anything was filed: the ones that did arrive are filed
                // and the rest were never here, so the person is the only one who can tell.
                if (ProposalPapers::cutShort($this->getRequest()->getUploadedFiles())) {
                    $this->Flash->warning(ProposalPapers::shortfall());
                }

                if ($filed > 0) {
                    $this->Flash->success(
                        __n('{0} page has been filed.', '{0} pages have been filed.', $filed, $filed),
                    );

                    return $this->backToTheWorkbench($round);
                }

                $this->Flash->error(__('Nothing was chosen to file.'));
            } catch (Throwable $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        $this->set('round', $round);
        $this->set('about', $this->whatTheRoundIsCalled($round));
        $this->set('printed', $printed);
        $this->set('variants', DocumentVariant::received());

        return null;
    }

    /**
     * Lets go of one page.
     *
     * Letting go of something we drew up is not tidying: it puts the document back within reach,
     * because what is on file is never drawn again while it is there.
     *
     * @param string|null $link_id Which page.
     * @return \Cake\Http\Response|null Redirects back to the papers.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When it is not this round's.
     */
    public function dropPage(?string $link_id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $round = $this->roundAsked();
        $page = $this->thePage($round, $link_id);

        try {
            $dropped = (new ProposalPapers())->drop($page);
            $this->Flash->success(__n(
                'The page has been removed.',
                '{0} pages have been removed.',
                $dropped,
                $dropped,
            ));
        } catch (Throwable $e) {
            $this->Flash->error(__('The page could not be removed: {0}', $e->getMessage()));
        }

        return $this->backToTheWorkbench($round);
    }

    /**
     * Moves one page past the one beside it.
     *
     * @param string|null $link_id Which page.
     * @param string|null $direction Which way - `up` or anything else for down.
     * @return \Cake\Http\Response|null Redirects back to the papers.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When it is not this round's.
     */
    public function movePage(?string $link_id = null, ?string $direction = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'put']);

        $round = $this->roundAsked();
        $page = $this->thePage($round, $link_id);

        try {
            (new ProposalPapers())->move($page, $direction === 'up');
        } catch (Throwable $e) {
            $this->Flash->error(__('The pages could not be reordered: {0}', $e->getMessage()));
        }

        return $this->backToTheWorkbench($round);
    }

    /**
     * Every paper the round may get back, by the record each hangs on.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The one opened.
     * @return array<string, array<string, mixed>>
     */
    private function whatMayComeBackFor(ContractProposal|CustomerProposal $round): array
    {
        $envelope = $round instanceof CustomerProposal ? $round : $round->customer_proposal;

        if ($envelope === null) {
            return [];
        }

        $printed = (new RoundOfPapers())->documentsAcross($envelope);

        // Opened on the papers of one contract, the page is about those papers - the rest of the
        // envelope is filed from the envelope.
        return $round instanceof CustomerProposal
            ? $printed
            : array_intersect_key($printed, [(string)$round->id => true]);
    }

    /**
     * One of this round's pages.
     *
     * The round is checked as well as the page, so that an identifier from somewhere else cannot
     * reach a paper through this door.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round The one opened.
     * @param string|null $link_id Which page.
     * @return \Files\Model\Entity\FileLink
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When it is not this round's.
     */
    private function thePage(
        ContractProposal|CustomerProposal|null $round,
        ?string $link_id,
    ): FileLink {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        /** @var \Files\Model\Entity\FileLink $link */
        $link = $links->get($link_id);

        $model = $round instanceof CustomerProposal
            ? CustomerDocuments::MODEL
            : ContractDocuments::MODEL;

        if ($round === null || $link->model !== $model || $link->foreign_key !== $round->id) {
            throw new RecordNotFoundException(__('That page belongs to something else.'));
        }

        return $link;
    }

    /**
     * Back to where the papers are worked on, at the round they were worked on from.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round The one opened.
     * @return \Cake\Http\Response|null
     */
    private function backToTheWorkbench(
        ContractProposal|CustomerProposal|null $round,
    ): ?Response {
        return $this->redirect([
            'action' => 'manage',
            '?' => array_filter([
                'proposal_id' => $round?->id,
                'agenda' => $round instanceof ContractProposal ? 'ContractProposals' : 'CustomerProposals',
            ]),
        ]);
    }

    /**
     * Which scope the documents table is drawn at, following the address inwards.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round The round asked for.
     * @return array{0: string, 1: string} What is being looked at, and which one.
     */
    private function scopeOfTheTable(
        ContractProposal|CustomerProposal|null $round,
        ?ContractVersion $version,
    ): array {
        if ($round instanceof CustomerProposal) {
            return ['customerProposal', (string)$round->id];
        }

        if ($round instanceof ContractProposal) {
            return ['contractProposal', (string)$round->id];
        }

        if ($version !== null) {
            return ['contractVersion', (string)$version->id];
        }

        return $this->contract_id === null
            ? ['customer', (string)$this->customer_id]
            : ['contract', $this->contract_id];
    }

    /**
     * The address the papers are worked on at, where it is not the one that was asked for.
     *
     * Papers know whose they are, and those of a contract which contract and which version they
     * speak about, so a link that only said which papers gets the rest of the address filled in. The whereabouts, the heading
     * and what is listed all read the address, so an address that says less shows less than it
     * could - and a bookmark is put right the same way a link is.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round The one opened.
     * @param \App\Model\Entity\ContractVersion|null $version The version in view.
     * @return \Cake\Http\Response|null
     */
    private function whereTheRoundIsWorkedOn(
        ContractProposal|CustomerProposal|null $round,
        ?ContractVersion $version,
    ): ?Response {
        if (!$this->getRequest()->is('get')) {
            return null;
        }

        $known = [
            'customer_id' => $this->customer_id,
            'contract_id' => $this->contract_id,
            'contract_version_id' => $this->contract_version_id,
        ];

        $its = ['customer_id' => null, 'contract_id' => null, 'contract_version_id' => null];

        if ($round instanceof ContractProposal) {
            $its['customer_id'] = $round->contract?->customer_id;
            $its['contract_id'] = $round->contract_id;
            $its['contract_version_id'] = $round->contract_version_id;
        } elseif ($round instanceof CustomerProposal) {
            $its['customer_id'] = $round->customer_id;
        } elseif ($version !== null) {
            $its['contract_id'] = $version->contract_id;
            $its['contract_version_id'] = (string)$version->id;
        }

        $filling = [];

        foreach ($its as $field => $said) {
            if (is_string($said) && $said !== '' && $known[$field] !== $said) {
                $filling[$field] = $said;
            }
        }

        if ($filling === []) {
            return null;
        }

        $query = $this->getRequest()->getQueryParams();
        unset($query['contract_version_id']);

        return $this->redirect([
            'action' => 'manage',
            '?' => $query,
        ] + $filling + $known);
    }

    /**
     * What a round is called wherever a page is headed with it.
     *
     * A proposal put to the customer says what it asks for and from when; the papers of a contract
     * name that contract as well, because beside it they would otherwise be one of several.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The one opened.
     * @return string
     */
    private function whatTheRoundIsCalled(ContractProposal|CustomerProposal $round): string
    {
        return $round instanceof CustomerProposal
            ? __('{0} from {1}', $round->whatItIsFor(), $round->effective_from)
            : $round->getName();
    }

    /**
     * What the table of papers is of, for the heading above it.
     *
     * The innermost thing the address names - the proposal, the version, the contract, or the
     * customer when it narrows no further. The words are the ones the whereabouts uses for the
     * same step, so the heading and the path through to it read alike.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round The one opened.
     * @param \App\Model\Entity\ContractVersion|null $version The version in view.
     * @param \App\Model\Entity\Contract|null $contract The contract in view.
     * @param \App\Model\Entity\Customer $customer Whose papers these are.
     * @return string
     */
    private function whatTheTableIsOf(
        ContractProposal|CustomerProposal|null $round,
        ?ContractVersion $version,
        ?Contract $contract,
        Customer $customer,
    ): string {
        if ($round !== null) {
            return $this->whatTheRoundIsCalled($round);
        }

        if ($version !== null) {
            return (string)$version->name;
        }

        if ($contract !== null) {
            return $contract->getName();
        }

        return $customer->getName();
    }

    /**
     * The version the address narrows to, where it narrows to one.
     *
     * A storey of its own between the contract and the papers: sometimes what somebody is dealing
     * with is one version of a contract and nothing else of it.
     *
     * @return \App\Model\Entity\ContractVersion|null
     */
    private function versionAsked(): ?ContractVersion
    {
        $id = $this->contract_version_id ?? $this->getRequest()->getQuery('contract_version_id');

        if (!is_string($id) || $id === '') {
            return null;
        }

        /** @var \App\Model\Entity\ContractVersion|null $version */
        $version = $this->fetchTable('ContractVersions')
            ->find()
            ->where(['ContractVersions.id' => $id])
            ->first();

        return $version;
    }

    /**
     * The round the address dives into, where it dives into one.
     *
     * @return \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null
     */
    private function roundAsked(): ContractProposal|CustomerProposal|null
    {
        $id = $this->getRequest()->getQuery('proposal_id');
        $agenda = (string)$this->getRequest()->getQuery('agenda');

        if (!is_string($id) || $id === '') {
            return null;
        }

        if (!in_array($agenda, self::AGENDAS, true)) {
            // An address that names a round without saying which side it is on is answered by
            // looking, rather than by an error about a field nobody typed.
            $agenda = $this->fetchTable('CustomerProposals')->exists(['id' => $id])
                ? 'CustomerProposals'
                : 'ContractProposals';
        }

        $query = $this->fetchTable($agenda)->find()->where(["{$agenda}.id" => $id]);

        // Papers of a contract are a part of a proposal, and the way out of them runs through it -
        // which the proposal cannot say without knowing what else it holds.
        $query->contain($agenda === 'ContractProposals'
            ? ['CustomerProposals' => ['ContractProposals'], 'Contracts']
            : ['ContractProposals']);

        /** @var \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $round */
        $round = $query->first();

        return $round;
    }

    /**
     * The proposals the address is about, newest first.
     *
     * One proposal is what anybody draws up: it is put to the customer, and what it does for each
     * of their contracts is a part of it. So a listing lists proposals, and a row says which
     * contracts its parts are about and what is asked of each.
     *
     * @param \App\Model\Entity\ContractVersion|null $version The version it narrows to, if any.
     * @param string $finder Which of them - all of them, or only those still open.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function roundsInView(?ContractVersion $version = null, string $finder = 'all'): SelectQuery
    {
        $proposals = $this->fetchTable('CustomerProposals')
            ->find($finder)
            ->contain(['Customers', 'ContractProposals' => ['Contracts']])
            ->orderByDesc('CustomerProposals.effective_from');

        if ($this->customer_id !== null) {
            $proposals->where(['CustomerProposals.customer_id' => $this->customer_id]);
        }

        // Standing on one contract - or on one of its versions - the proposals worth listing are
        // the ones that say something about it. The innermost storey of the address is what they
        // are asked about, so a version narrows this table as well as the papers below it.
        $narrowedTo = $version !== null
            ? ['ContractProposals.contract_version_id' => $version->id]
            : ($this->contract_id !== null ? ['ContractProposals.contract_id' => $this->contract_id] : null);

        if ($narrowedTo !== null) {
            $papers = $this->fetchTable('ContractProposals');

            $about = $papers->find()
                ->select(['ContractProposals.customer_proposal_id'])
                ->where($narrowedTo);

            $either = [['CustomerProposals.id IN' => $about]];

            if ($version === null) {
                $anyAtAll = $papers->find()
                    ->select(['ContractProposals.customer_proposal_id'])
                    ->where(['ContractProposals.customer_proposal_id IS NOT' => null]);

                // One that holds nothing at all and is still open is a proposal somebody has just
                // drawn up: this contract is what it is waiting for. Settled ones are not waiting
                // for anything, and under a version there is nothing to hold it open for.
                $either[] = [
                    'CustomerProposals.id IN' => $this->fetchTable('CustomerProposals')
                        ->find('open')
                        ->select(['CustomerProposals.id'])
                        ->where(['CustomerProposals.id NOT IN' => $anyAtAll]),
                ];
            }

            $proposals->where(['OR' => $either]);
        }

        return $proposals;
    }

    /**
     * The rounds the table is really about, which are the ones somebody may still do something
     * with.
     *
     * A round given up on is not work any more and would only stand between the reader and what
     * is, so it steps out until they ask for it. Whatever the page is standing on stays listed
     * either way: leaving out the very round whose papers are underneath would say the workbench
     * is about nothing.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $rounds The rounds in view.
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal|null $inView What the page is on.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    private function whatIsStillWorkedOn(
        SelectQuery $rounds,
        ContractProposal|CustomerProposal|null $inView,
    ): SelectQuery {
        if ($this->alsoWhatWasGivenUpOn()) {
            return $rounds;
        }

        $standing = ['CustomerProposals.revoked IS' => null];
        $stoodOn = $inView instanceof ContractProposal ? $inView->customer_proposal_id : $inView?->id;

        return $rounds->where(
            $stoodOn === null ? $standing : ['OR' => [$standing, ['CustomerProposals.id' => $stoodOn]]],
        );
    }

    /**
     * Whether the reader asked to see what was given up on as well.
     *
     * @return bool
     */
    private function alsoWhatWasGivenUpOn(): bool
    {
        return toBool($this->getRequest()->getQuery('show_revoked')) ?? false;
    }

    /**
     * What a word typed into the register is taken to mean.
     *
     * A number is how somebody names a customer or a contract, and that is what they have in front
     * of them - the paperwork itself carries neither, so the contract is asked through the papers
     * that are part of the proposal. Anything else is looked for in the note, which is the only
     * prose a proposal holds.
     *
     * @param string $search What was typed.
     * @return array<string, mixed>
     */
    private function whatIsBeingLookedFor(string $search): array
    {
        $said = ['CustomerProposals.note ILIKE' => '%' . $search . '%'];

        $ofAContract = $this->fetchTable('ContractProposals')
            ->find()
            ->select(['ContractProposals.customer_proposal_id'])
            ->innerJoinWith('Contracts')
            ->where(['Contracts.number ILIKE' => '%' . $search . '%']);

        $said['CustomerProposals.id IN'] = $ofAContract;

        if (ctype_digit($search) && strlen($search) <= 10) {
            $series = (int)Configure::read('Customers.series');
            $said['(Customers.nid + ' . $series . ') ='] = (int)$search;
        }

        return ['OR' => $said];
    }

    /**
     * The proposals as the table reads them.
     *
     * @param iterable<\Cake\Datasource\EntityInterface> $proposals The ones in view.
     * @return array<array<string, mixed>>
     */
    private function asRows(iterable $proposals): array
    {
        $listed = [];

        foreach ($proposals as $proposal) {
            /** @var \App\Model\Entity\CustomerProposal $proposal */
            $listed[] = $this->asARow($proposal);
        }

        return $listed;
    }

    /**
     * One round, as a listing wants it.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The round.
     * @return array<string, mixed>
     */
    private function asARow(ContractProposal|CustomerProposal $round): array
    {
        $ofAContract = $round instanceof ContractProposal;

        return [
            'id' => (string)$round->id,
            'agenda' => $ofAContract ? 'ContractProposals' : 'CustomerProposals',
            'round' => $round,
            'customer' => $ofAContract
                ? ($round->contract->customer ?? null)
                : ($round->customer ?? null),
            'contract' => $ofAContract ? ($round->contract ?? null) : null,
            'covers' => $ofAContract ? [] : $this->contractsCovered($round),
            'version' => $ofAContract ? ($round->contract_version ?? null) : null,
            'purpose' => $ofAContract ? $round->purpose->label() : $round->whatItIsFor(),
        ];
    }

    /**
     * Which contracts a proposal says something about, by their numbers.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<string>
     */
    private function contractsCovered(CustomerProposal $proposal): array
    {
        $covered = [];

        foreach ($proposal->contract_proposals ?? [] as $papers) {
            $number = (string)($papers->contract->number ?? '');

            if ($number === '') {
                continue;
            }

            // The number says which contract, the day and the purpose what is asked of it and
            // from when. Together they are the whole of what a part amounts to, which is why the
            // parts are not listed a second time underneath - and the day is the part's own,
            // which need not be the day the proposal speaks from.
            $said = __(
                '{0} ({1} - {2})',
                $number,
                (string)$papers->effective_from,
                $papers->purpose->label(),
            );
            $covered[$said] = $said;
        }

        sort($covered);

        return $covered;
    }

    /**
     * Hands a paper over to whoever asked for it.
     *
     * Shown rather than downloaded: printing is what this is for, and a paper that opens is one
     * fewer step than a paper that lands in a folder.
     *
     * @param \App\Documents\PrintedDocument $document The paper.
     * @return \Cake\Http\Response
     */
    private function handOver(PrintedDocument $document): Response
    {
        return (new Response())
            ->withType($document->mimeType)
            ->withHeader('Content-Disposition', 'inline; filename="' . $document->filename . '"')
            ->withStringBody($document->bytes);
    }
}
