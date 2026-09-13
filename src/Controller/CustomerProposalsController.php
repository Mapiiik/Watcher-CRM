<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\DocumentVariant;
use App\Proposals\ProposalPapers;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Throwable;

/**
 * CustomerProposals Controller
 *
 * A round of papers put to the customer themselves rather than to any one contract. It travels the
 * road a contract's proposal does - drawn up, sent, signed - and stops there, because nothing
 * stands behind it waiting to be written into the live records.
 *
 * @property \App\Model\Table\CustomerProposalsTable $CustomerProposals
 */
class CustomerProposalsController extends AppController
{
    /**
     * Every page here is about one proposal, and the bar over it is what says whose papers
     * these are - so a page asked for without the nesting is sent to where it belongs.
     *
     * @var list<string>
     */
    protected array $nestingAutoFix = [
        'view',
        'edit',
        'documents',
        'conclude',
        'send',
        'addPages',
    ];

    /**
     * @var list<string>
     */
    protected array $nestingAutoAdd = [
        'view',
        'edit',
        'documents',
        'conclude',
        'send',
        'addPages',
    ];

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions += ['CustomerProposals.customer_id' => $this->customer_id];
        }

        $request = $this->getRequest();
        $show_settled = toBool($request->getQuery('show_settled')) ?? false;

        $search = $request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = ['CustomerProposals.note ILIKE' => '%' . trim((string)$search) . '%'];
        }

        $query = $this->CustomerProposals
            ->find($show_settled ? 'all' : 'open')
            ->contain(['Customers'])
            ->where($conditions)
            ->orderBy(['CustomerProposals.effective_from' => 'DESC']);

        $customerProposals = $this->paginate($query);

        $this->set(compact('customerProposals', 'show_settled'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer proposal id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $customerProposal = $this->CustomerProposals->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('customerProposal'));
        $this->set('mayBeEdited', $this->CustomerProposals->mayBeEdited($customerProposal));
        $this->set('mayBeDeleted', $this->CustomerProposals->mayBeDeleted($customerProposal));
        // Only the count: the tables themselves are drawn by a cell, which asks for what it draws.
        $this->set('filed', (new CustomerDocuments())->filedAgainst([$customerProposal])[$customerProposal->id] ?? []);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $proposal = $this->CustomerProposals->newEmptyEntity();

        if ($this->customer_id !== null) {
            $proposal->set('customer_id', $this->customer_id);
        }

        // A paper asked for out of the blue speaks about today. Where it goes out with something
        // else - a contract starting, say - whoever draws it up says which day that is.
        $proposal->set('effective_from', Date::now());

        if ($this->request->is('post')) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, $this->request->getData());

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be saved. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->setFormViewVars();

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id);

        if (!$this->CustomerProposals->mayBeEdited($proposal)) {
            $this->Flash->warning(__('This proposal may no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, $this->request->getData());

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been saved.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be saved. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->setFormViewVars();

        return null;
    }

    /**
     * Records that the papers went out, and how.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function send(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, [
                'sent_date' => $this->request->getData('sent_date'),
                'delivery_type' => $this->request->getData('delivery_type'),
            ]);

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been recorded as sent.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The sending could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->set('deliveryTypes', DocumentsDeliveryType::options());

        return null;
    }

    /**
     * Records the day the customer signed.
     *
     * This is where a round ends: nothing stands behind it waiting to be carried over.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function conclude(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers']);

        if ($proposal->hasBeenRevoked()) {
            $this->Flash->warning(__('This round of papers has been given up on.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, [
                'conclusion_date' => $this->request->getData('conclusion_date'),
            ]);

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The signature has been recorded.'));
                $this->fileWhatCameWithIt($proposal);

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The signature could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        // Only what was actually printed: nothing else can have come back.
        $this->set('printed', (new CustomerDocuments())->printedTypes($proposal));
        $this->set('variants', DocumentVariant::received());

        return null;
    }

    /**
     * Files the scans that came in with the signature.
     *
     * After the day is recorded rather than with it: the day is what the round turns on, and a
     * scan that will not be stored must not stand in the way of it.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal Whose papers.
     * @return void
     */
    private function fileWhatCameWithIt(CustomerProposal $proposal): void
    {
        $uploaded = $this->getRequest()->getUploadedFiles()['papers'] ?? [];
        if (!is_array($uploaded)) {
            return;
        }

        $came = (new ProposalPapers())->takeEach(
            CustomerDocuments::MODEL,
            (string)$proposal->id,
            $uploaded,
            (array)$this->getRequest()->getData('variants'),
        );

        foreach ($came['problems'] as $problem) {
            $this->Flash->error($problem);
        }

        // Said whether or not anything was filed: the ones that did arrive are filed and the
        // rest were never here, so the person is the only one who can tell.
        if (ProposalPapers::cutShort($this->getRequest()->getUploadedFiles())) {
            $this->Flash->warning(ProposalPapers::shortfall());
        }

        if ($came['filed'] > 0) {
            $this->Flash->success(
                __n('{0} page has been filed.', '{0} pages have been filed.', $came['filed'], $came['filed']),
            );
        }
    }

    /**
     * Gives up on the papers.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects back to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function revoke(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->CustomerProposals->get($id);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $proposal->revoked = DateTime::now();
        $proposal->revoked_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->CustomerProposals->save($proposal, ['checkRules' => false])) {
            $this->Flash->success(__('The proposal has been revoked.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be revoked. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $proposal = $this->CustomerProposals->get($id);

        if (!$this->CustomerProposals->mayBeDeleted($proposal)) {
            $this->Flash->warning(__('This proposal may no longer be removed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->CustomerProposals->delete($proposal)) {
            $this->Flash->success(__('The proposal has been deleted.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * The papers this round has: what we drew up, and what came back.
     *
     * Not gated on the state of the round. Scans arrive after it has been sent and after the
     * signature has been recorded, and the papers stay worth looking at for as long as the
     * customer does.
     *
     * @param string|null $id Customer proposal id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function documents(?string $id = null): void
    {
        $this->set('customerProposal', $this->CustomerProposals->get($id, contain: ['Customers']));
    }

    /**
     * Files what came back, a document at a time.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when filed, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function addPages(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $document_type = (string)$this->getRequest()->getData('document_type');
            $variant = DocumentVariant::tryFrom((string)$this->getRequest()->getData('variant'));
            $files = $this->getRequest()->getUploadedFiles()['papers'] ?? [];

            if ($variant === null || $document_type === '' || !is_array($files)) {
                $this->Flash->error(__('It was not said what those pages are.'));

                return null;
            }

            try {
                $filed = (new ProposalPapers())->take(
                    CustomerDocuments::MODEL,
                    (string)$proposal->id,
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

                    return $this->redirect(['action' => 'documents', $id]);
                }

                $this->Flash->error(__('Nothing was chosen to file.'));
            } catch (Throwable $e) {
                $this->Flash->error($e->getMessage());
            }
        }

        $this->set('customerProposal', $proposal);
        $this->set('documentTypes', $this->documentLabels($proposal));
        $this->set('variants', DocumentVariant::received());

        return null;
    }

    /**
     * Lets go of one page.
     *
     * Letting go of something we drew up is not tidying: it puts the document back within reach,
     * because what is on file is never drawn again while it is there.
     *
     * @param string|null $id Customer proposal id.
     * @param string|null $link_id Which page.
     * @return \Cake\Http\Response|null Redirects back to the papers.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dropPage(?string $id = null, ?string $link_id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $page = $this->thePage($id, $link_id);

        try {
            (new ProposalPapers())->drop($page);
            $this->Flash->success(__('The page has been removed.'));
        } catch (Throwable $e) {
            $this->Flash->error(__('The page could not be removed: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'documents', $id]);
    }

    /**
     * Moves one page past the one beside it.
     *
     * @param string|null $id Customer proposal id.
     * @param string|null $link_id Which page.
     * @param string|null $direction Which way - `up` or anything else for down.
     * @return \Cake\Http\Response|null Redirects back to the papers.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function movePage(?string $id = null, ?string $link_id = null, ?string $direction = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'put']);

        $page = $this->thePage($id, $link_id);

        try {
            (new ProposalPapers())->move($page, $direction === 'up');
        } catch (Throwable $e) {
            $this->Flash->error(__('The pages could not be reordered: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'documents', $id]);
    }

    /**
     * One of this round's pages.
     *
     * The round is checked as well as the page, so that an identifier from somewhere else cannot
     * reach a paper through this door.
     *
     * @param string|null $id Customer proposal id.
     * @param string|null $link_id Which page.
     * @return \Files\Model\Entity\FileLink
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When it is not this round's.
     */
    private function thePage(?string $id, ?string $link_id): FileLink
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        /** @var \Files\Model\Entity\FileLink $link */
        $link = $links->get($link_id);

        if ($link->model !== CustomerDocuments::MODEL || $link->foreign_key !== $id) {
            throw new RecordNotFoundException(__('That page belongs to something else.'));
        }

        return $link;
    }

    /**
     * The documents this round may be printed as, and so may have papers for.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The round.
     * @return array<string, string>
     */
    private function documentLabels(CustomerProposal $proposal): array
    {
        $documents = [];

        foreach ($proposal->purpose->documents() as $document) {
            $documents[$document->value] = $document->label();
        }

        return $documents;
    }

    /**
     * What the form needs to draw itself.
     *
     * @return void
     */
    private function setFormViewVars(): void
    {
        $this->set('purposes', CustomerProposalPurpose::options());
        $this->set('customers', $this->CustomerProposals->Customers->find('list'));
    }
}
