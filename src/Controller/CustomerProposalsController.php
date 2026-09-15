<?php
declare(strict_types=1);

namespace App\Controller;

use App\Contracts\Proposal\PlannedChange;
use App\Contracts\Proposal\ProposalProjection;
use App\Contracts\Proposal\ProposalTransfer;
use App\Contracts\Proposal\TransferPlan;
use App\Contracts\Proposal\TransferPreview;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\ProposalStep;
use App\Model\Table\BillingsTable;
use App\Proposals\RoundOfPapers;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Exception;

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
        'conclude',
        'send',
        'transfer',
    ];

    /**
     * @var list<string>
     */
    protected array $nestingAutoAdd = [
        'view',
        'edit',
        'conclude',
        'send',
        'transfer',
    ];

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
            'ContractProposals',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('customerProposal'));
        // What the proposal does for each contract, read - and changed - where the rest of it is.
        $this->set('parts', $this->whatEachPartSays($customerProposal));
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
        $proposal->set('contract_proposals', []);

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

                // A proposal is read after it is written, not the record it hangs on: what was
                // just said about it is the thing worth seeing, and the way back to the card is
                // on the page.
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
     * Edit method
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers', 'ContractProposals']);

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
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers', 'ContractProposals']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $said = [
                'sent_date' => $this->request->getData('sent_date'),
                'delivery_type' => $this->request->getData('delivery_type'),
            ];
            $proposal = $this->CustomerProposals->patchEntity($proposal, $said);

            if ($this->recordAcrossTheRound($proposal, ProposalStep::Delivered)) {
                $this->Flash->success(__('The proposal has been recorded as sent.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The sending could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->set('deliveryTypes', DocumentsDeliveryType::options());
        $this->set('alsoInTheRound', (new RoundOfPapers())->whateverTheStepReaches(
            $proposal->id,
            ProposalStep::Delivered,
        ));

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
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers', 'ContractProposals']);

        if ($proposal->hasBeenRevoked()) {
            $this->Flash->warning(__('This round of papers has been given up on.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $said = ['conclusion_date' => $this->request->getData('conclusion_date')];
            $proposal = $this->CustomerProposals->patchEntity($proposal, $said);

            if ($this->recordAcrossTheRound($proposal, ProposalStep::Signed)) {
                $this->Flash->success(__('The signature has been recorded.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The signature could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->set('alsoInTheRound', (new RoundOfPapers())->whateverTheStepReaches(
            $proposal->id,
            ProposalStep::Signed,
        ));

        return null;
    }

    /**
     * Writes the step onto the round, which is what everything in it reads.
     *
     * One place rather than one place per set of papers: the envelope went out in one piece and
     * came back in one, so the day is the envelope's and the papers inside it answer with it.
     *
     * A signed consent is also what the customer's own record has been waiting for. The flag used
     * to be ticked by hand, so a customer could read as having refused while their signed consent
     * sat on file - this is the same thing the contract's side does when papers are carried over.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The round.
     * @param \App\Model\Enum\ProposalStep $step Which step is being taken.
     * @return bool
     */
    private function recordAcrossTheRound(
        CustomerProposal $proposal,
        ProposalStep $step,
    ): bool {
        return (bool)$this->CustomerProposals->getConnection()->transactional(
            function () use ($proposal, $step): bool {
                if (!$this->CustomerProposals->save($proposal)) {
                    return false;
                }

                if ($step === ProposalStep::Signed && $proposal->purpose === CustomerProposalPurpose::GdprConsent) {
                    $customers = $this->CustomerProposals->Customers;
                    $customer = $customers->get($proposal->customer_id);

                    if ($customer->agree_gdpr !== true) {
                        $customers->saveOrFail($customers->patchEntity($customer, [
                            'agree_gdpr' => true,
                        ]));
                    }
                }

                return true;
            },
        );
    }

    /**
     * Carries what the whole proposal asks for into the live records.
     *
     * Spelled out a contract at a time, because that is what each part asks about and what each
     * one would write - one merged list would say what is happening to nothing in particular.
     *
     * All or none. A package half written into the records is the worst of both: nothing says
     * which half, and the papers it came from read as settled either way.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when carried over, renders the preview otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function transfer(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers', 'ContractProposals']);

        $preview = new TransferPreview();
        $plan = new TransferPlan();
        $parts = [];
        $stopped = false;

        foreach ((new RoundOfPapers())->partsOf((string)$proposal->id) as $papers) {
            if (!$papers->isDueFor(ProposalStep::CarriedOver)) {
                continue;
            }

            $found = $preview->of($papers);
            $stopped = $stopped || $preview->anythingStopsIt($found);

            $parts[] = [
                'papers' => $papers,
                'found' => $found,
                'planned' => $plan->of($papers),
                'billingsNow' => $preview->billingsNow($papers),
                'billingsAfterwards' => $preview->billingsAfterwards($papers),
            ];
        }

        if ($parts === []) {
            $this->Flash->warning(__('There is nothing in this proposal left to carry over.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($stopped) {
                $this->Flash->error(__('This proposal cannot be carried over as it stands.'));
            } elseif ($this->carryTheWholePackageOver($parts)) {
                return $this->redirect(['action' => 'view', $id]);
            }
        }

        $this->set('customerProposal', $proposal);
        $this->set('parts', $parts);
        $this->set('stopped', $stopped);
        $this->set('closed_period_override', $this->mayReachIntoClosedPeriods());

        return null;
    }

    /**
     * Writes every part of the package, or none of it.
     *
     * @param array<array<string, mixed>> $parts What is to be carried over.
     * @return bool
     */
    private function carryTheWholePackageOver(array $parts): bool
    {
        $by = $this->getRequest()->getAttribute('identity')['id'] ?? null;
        $reaching = $this->mayReachIntoClosedPeriods()
            && $this->request->getData(BillingsTable::ALLOW_CLOSED_PERIODS) == '1';

        try {
            $this->CustomerProposals->getConnection()->transactional(
                function () use ($parts, $by, $reaching): void {
                    foreach ($parts as $part) {
                        (new ProposalTransfer())->carryOver($part['papers'], $by, $reaching);
                    }
                },
            );
        } catch (Exception $failure) {
            $this->Flash->error(__(
                'The proposal could not be carried over: {0}',
                $failure->getMessage(),
            ));

            return false;
        }

        $this->Flash->success(__n(
            'The proposal has been carried over into the live records.',
            'The proposal and everything in it have been carried over into the live records.',
            count($parts),
        ));

        foreach ($parts as $part) {
            if ($part['papers']->proposedChanges()->contract->endsTheContract()) {
                $this->Flash->warning(__(
                    'Contract {0} has been given an end date. Its state is left as it was, because'
                    . ' that has its own requirements to satisfy.',
                    $part['papers']->contract->number ?? '',
                ));
            }
        }

        return true;
    }

    /**
     * What the proposal says about each contract, in the shape the page that draws it wants.
     *
     * The same answers the papers of one contract give on their own page, because it is the same
     * question - and there is one proposal, so it is asked in one place.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<array<string, mixed>>
     */
    private function whatEachPartSays(CustomerProposal $proposal): array
    {
        $papers = $this->CustomerProposals->ContractProposals;
        $said = [];

        foreach ((new RoundOfPapers())->partsOf((string)$proposal->id) as $part) {
            $changes = $part->proposedChanges();
            $snapshot = $part->stateOfThings();

            $said[] = [
                'papers' => $part,
                'confirmations' => $part->confirmations(),
                'mayBeEdited' => $papers->mayBeEdited($part),
                // The same projection the documents print from, so the table and the paper cannot
                // disagree.
                'rows' => (new ProposalProjection())->explain(
                    $snapshot->hydrate()->billings,
                    $changes,
                    $part->effective_from,
                    $snapshot->servicesChosenBy($changes),
                ),
                // Only what the papers ask for: the rest of what carrying them over would write is
                // worked out against the records as they stand, which means something in the
                // moment before it happens and nothing here.
                'planned' => array_values(array_filter(
                    (new TransferPlan())->of($part),
                    fn(PlannedChange $one): bool => $one->asked,
                )),
            ];
        }

        return $said;
    }

    /**
     * Whether this request is one that may be offered the way into an invoiced period at all.
     *
     * Offered to an administrator and to nobody else, and even there it is a box that has to be
     * ticked - the same gate carrying one contract's papers over has always had.
     *
     * @return bool
     */
    private function mayReachIntoClosedPeriods(): bool
    {
        return ($this->getRequest()->getAttribute('identity')['role'] ?? null) === 'admin';
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

        $proposal = $this->CustomerProposals->get($id, contain: ['ContractProposals']);

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

        $proposal = $this->CustomerProposals->get($id, contain: ['ContractProposals']);

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

        return $this->afterDeleteRedirect([
            'controller' => 'Documents',
            'action' => 'index',
        ]);
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
