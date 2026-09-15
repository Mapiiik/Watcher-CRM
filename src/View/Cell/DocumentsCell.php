<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Enum\DocumentVariant;
use App\Model\Table\ContractProposalsTable;
use App\Model\Table\CustomerProposalsTable;
use App\Proposals\DrawnPaper;
use App\Proposals\WhatIsOwed;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\View\Cell;
use InvalidArgumentException;
use Override;

/**
 * The documents filed against a record, wherever somebody is looking at that record.
 *
 * A cell rather than an element, so that a page says which record it is about and nothing else. It
 * was an element first, and every page that wanted the table had to remember to load the rounds
 * and their papers and to say which columns to draw - three chances to get it wrong for one table.
 *
 * Which columns those are follows from what is being looked at rather than being asked for: a
 * contract's own page has no business repeating the contract on every row.
 *
 * The two agendas share the table. A contract's papers hang off a proposal of that contract, and
 * that is a part of the proposal put to the customer - so a proposal shows the whole package, and
 * so does the customer's own page. A paper of the customer's own belongs to no contract, and that
 * column is simply empty on its rows.
 */
class DocumentsCell extends Cell
{
    /**
     * What the papers may be looked at from.
     *
     * @var list<string>
     */
    public const SCOPES = ['contractProposal', 'customerProposal', 'contractVersion', 'contract', 'customer'];

    /**
     * List of valid options that can be passed into this cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = [
        'generatedByUs',
        'manage',
        'withContracts',
        'thumbnails',
        'withWhatIsMissing',
    ];

    /**
     * Whether this is the side we generated rather than the scans that came back.
     */
    protected bool $generatedByUs = false;

    /**
     * Whether the pages may be reordered and let go of from here.
     */
    protected bool $manage = false;

    /**
     * Whether the papers of the contracts belong here too, beside those put to the customer.
     *
     * Asked where a record holds contracts under it - a customer, or one proposal put to them. A
     * proposal is one envelope and its parts go out in it, so the answer is yes unless somebody
     * says otherwise.
     */
    protected bool $withContracts = true;

    /**
     * Whether each page shows what it looks like.
     *
     * Off unless asked for, and asked for separately from `manage`. That somebody may reorder the
     * pages here and that they want to see them are two different questions: a page can want the
     * pictures without being able to let go of anything, and the other way round. On the wide
     * listings - a contract, a customer, a version, a printout - pictures would cost more in
     * readability than they give back.
     */
    protected bool $thumbnails = false;

    /**
     * Whether a round with nothing on file is listed all the same.
     *
     * A page that only lists papers cannot be used to add the first one, because the round it
     * would hang on is the one round with no row to start from. Off unless asked for: on a page
     * that is about reading what is there, a run of empty rows only gets in the way.
     *
     * A round that was revoked is left out when it has nothing, since nothing is ever coming. What
     * it does have stays listed, because those papers happened.
     */
    protected bool $withWhatIsMissing = false;

    /**
     * Initialization hook method.
     *
     * The viewer is the plugin's, and it is asked for here rather than in the application's view:
     * this is the only table that has documents to look through, and a page without one has no
     * business loading a helper for them.
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->addHelper('Files.Preview');
    }

    /**
     * Default display method.
     *
     * @param string $of What is being looked at - one of {@see self::SCOPES}.
     * @param string|null $id Which one.
     * @return void
     * @throws \InvalidArgumentException When asked for something that is not a scope.
     */
    public function display(string $of, ?string $id): void
    {
        if (!in_array($of, self::SCOPES, true)) {
            throw new InvalidArgumentException(sprintf('`%s` is not something documents hang on.', $of));
        }

        $this->set('rows', $id === null ? [] : $this->rows($of, $id));
        $this->set('generatedByUs', $this->generatedByUs);
        $this->set('manage', $this->manage);
        $this->set('thumbnails', $this->thumbnails);
        // Neither column says anything the page it is on has not already said. A proposal is
        // about several contracts at once, so there the column earns its place - as long as their
        // papers are in view at all.
        $this->set(
            'showContract',
            in_array($of, ['customerProposal', 'customer'], true) && $this->withContracts,
        );
        // The same holds for the round a paper belongs to: worth a column wherever more than
        // one of them is in the table, which a proposal is as soon as its parts are in view.
        $this->set('showProposal', in_array($of, ['contractVersion', 'contract', 'customer'], true)
            || ($of === 'customerProposal' && $this->withContracts));
    }

    /**
     * One row to a page, what was put to the customer first and the contracts' papers after it.
     *
     * That way round because a paper about the customer themselves is about all of them, while one
     * about a contract is about a corner of it.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return list<array<string, mixed>>
     */
    private function rows(string $of, string $id): array
    {
        $rows = [];

        $papers = new CustomerDocuments();
        $customers = $this->customerProposals($of, $id);
        $filed = $papers->filedAgainst($customers);
        $documents = $papers->documentLabels();

        $owed = new WhatIsOwed();

        foreach ($customers as $proposal) {
            $rows = array_merge($rows, $this->pagesOf([
                'id' => (string)$proposal->id,
                'controller' => 'CustomerProposals',
                'of' => $proposal,
                'owed' => $owed->of($proposal),
                'label' => $proposal->effective_from . ' - ' . $proposal->whatItIsFor(),
                // The column reads down a table, where the day leads and the dashes line up. The
                // viewer reads across one line, where it wants a sentence instead.
                'says' => __('{0} from {1}', $proposal->whatItIsFor(), $proposal->effective_from),
                // A consent belongs to nobody's contract, so the column stays empty on its rows.
                'contract_id' => null,
                'contract' => '',
                'revoked' => $proposal->hasBeenRevoked(),
                'documents' => $documents,
            ], $filed));
        }

        $papers = new ContractDocuments();
        $contracts = $this->contractProposals($of, $id);
        $filed = $papers->filedAgainst($contracts);
        $documents = $papers->documentLabels();

        foreach ($contracts as $proposal) {
            $rows = array_merge($rows, $this->pagesOf([
                'id' => (string)$proposal->id,
                'controller' => 'ContractProposals',
                'of' => $proposal,
                'owed' => $owed->of($proposal),
                'label' => $proposal->effective_from . ' - ' . $proposal->purpose->label(),
                'says' => __('{0} from {1}', $proposal->purpose->label(), $proposal->effective_from),
                'contract_id' => (string)$proposal->contract_id,
                'contract' => (string)($proposal->contract->number ?? ''),
                'revoked' => $proposal->hasBeenRevoked(),
                'documents' => $documents,
            ], $filed));
        }

        return $rows;
    }

    /**
     * The pages of one round, on the side being looked at.
     *
     * @param array<string, mixed> $round What they hang on.
     * @param array<string, array<string, array<string, list<\Files\Model\Entity\FileLink>>>> $filed What is on file.
     * @return list<array<string, mixed>>
     */
    private function pagesOf(array $round, array $filed): array
    {
        $rows = [];
        $drawn = [];

        $papers = new DrawnPaper();

        foreach ($filed[$round['id']] ?? [] as $document_type => $byVariant) {
            // Our signature is stamped onto the paper that is already there rather than drawn
            // afresh, so it is offered on that paper's own row and only while it is not there.
            $mayBeSigned = $this->generatedByUs
                && !isset($byVariant[DocumentVariant::GeneratedSignedByUs->value])
                && $papers->mayCarryOurSignature($round['of'], (string)$document_type);

            foreach ($byVariant as $variant => $links) {
                $case = DocumentVariant::tryFrom((string)$variant);
                if ($case === null || $case->isGeneratedByUs() !== $this->generatedByUs) {
                    continue;
                }

                $drawn[(string)$document_type] = true;

                foreach ($links as $link) {
                    $rows[] = [
                        'round' => $round,
                        'document' => $round['documents'][$document_type] ?? (string)$document_type,
                        'document_type' => (string)$document_type,
                        'variant' => $case->label(),
                        'link' => $link,
                        'mayBeSigned' => $mayBeSigned && $case === DocumentVariant::Generated,
                        'keys' => [
                            'contract' => (string)($round['contract_id'] ?? ''),
                            'round' => $round['id'],
                            'document' => $round['id'] . '/' . $document_type,
                            'variant' => $round['id'] . '/' . $document_type . '/' . $variant,
                        ],
                    ];
                }
            }
        }

        if (!$this->withWhatIsMissing || $round['revoked'] === true) {
            return $rows;
        }

        // On the side we draw ourselves, what is missing is each paper that has not been drawn:
        // the round knows which ones it owes, so the gap can be named rather than guessed at. On
        // the side that comes back there is no such list - a round either has scans or it has not.
        if ($this->generatedByUs) {
            return array_merge($rows, $this->whatHasNotBeenDrawn($round, $drawn));
        }

        if ($rows === []) {
            $rows[] = $this->nothingYet($round, '', '');
        }

        return $rows;
    }

    /**
     * A row for each paper the round owes and does not have.
     *
     * Ordered as the round itself orders them, so that the same papers read the same way wherever
     * they are listed.
     *
     * @param array<string, mixed> $round What the papers hang on.
     * @param array<string, bool> $drawn Which of them are already on file.
     * @return list<array<string, mixed>>
     */
    private function whatHasNotBeenDrawn(array $round, array $drawn): array
    {
        $rows = [];
        $papers = new DrawnPaper();

        /** @var array<string, bool> $owed */
        $owed = $round['owed'] ?? [];

        foreach ($owed as $document_type => $required) {
            if (isset($drawn[(string)$document_type])) {
                continue;
            }

            $rows[] = $this->nothingYet(
                $round,
                (string)$document_type,
                $round['documents'][$document_type] ?? (string)$document_type,
                $required,
                $papers->mayCarryOurSignature($round['of'], (string)$document_type),
            );
        }

        return $rows;
    }

    /**
     * One row saying something is not there.
     *
     * @param array<string, mixed> $round What it would have hung on.
     * @param string $document_type Which paper, where that is known.
     * @param string $document What to call it.
     * @param bool $required Whether its absence is a gap rather than a choice.
     * @param bool $mayBeSigned Whether it may also be had with our signature on it.
     * @return array<string, mixed>
     */
    private function nothingYet(
        array $round,
        string $document_type,
        string $document,
        bool $required = true,
        bool $mayBeSigned = false,
    ): array {
        $of = $document_type === '' ? '-' : $document_type;

        return [
            'round' => $round,
            'document' => $document,
            'document_type' => $document_type,
            'variant' => '',
            'link' => null,
            'required' => $required,
            'mayBeSigned' => $mayBeSigned,
            'keys' => [
                'contract' => (string)($round['contract_id'] ?? ''),
                'round' => $round['id'],
                'document' => $round['id'] . '/' . $of,
                'variant' => $round['id'] . '/' . $of,
            ],
        ];
    }

    /**
     * The proposals of contracts in view.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return list<\App\Model\Entity\ContractProposal>
     */
    private function contractProposals(string $of, string $id): array
    {
        if (!$this->withContracts && in_array($of, ['customerProposal', 'customer'], true)) {
            return [];
        }

        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->fetchTable(ContractProposalsTable::class);

        $query = $proposals->find()->contain(['Contracts']);
        $query = match ($of) {
            'contractProposal' => $query->where(['ContractProposals.id' => $id]),
            'contractVersion' => $query->where(['ContractProposals.contract_version_id' => $id]),
            'contract' => $query->where(['ContractProposals.contract_id' => $id]),
            'customerProposal' => $query->where(['ContractProposals.customer_proposal_id' => $id]),
            default => $query
                ->where(['Contracts.customer_id' => $id])
                ->orderBy(['Contracts.number' => 'ASC']),
        };

        /** @var list<\App\Model\Entity\ContractProposal> $found */
        $found = $query->orderByDesc('ContractProposals.effective_from')->all()->toList();

        return $found;
    }

    /**
     * The rounds put to a customer in view.
     *
     * @param string $of What is being looked at.
     * @param string $id Which one.
     * @return list<\App\Model\Entity\CustomerProposal>
     */
    private function customerProposals(string $of, string $id): array
    {
        if (!in_array($of, ['customerProposal', 'customer'], true)) {
            return [];
        }

        /** @var \App\Model\Table\CustomerProposalsTable $proposals */
        $proposals = $this->fetchTable(CustomerProposalsTable::class);

        $query = $of === 'customerProposal'
            ? $proposals->find()->where(['CustomerProposals.id' => $id])
            : $proposals->find()->where(['CustomerProposals.customer_id' => $id]);

        // What a proposal calls itself takes in what it holds.
        $query->contain(['ContractProposals']);

        /** @var list<\App\Model\Entity\CustomerProposal> $found */
        $found = $query->orderByDesc('CustomerProposals.effective_from')->all()->toList();

        return $found;
    }
}
