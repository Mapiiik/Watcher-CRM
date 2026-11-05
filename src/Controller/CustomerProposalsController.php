<?php
declare(strict_types=1);

namespace App\Controller;

use App\Contracts\Proposal\ProposalReading;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\ProposalStep;
use App\Model\Table\BillingsTable;
use App\Proposals\WholeProposal;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\Http\Response;
use Cake\I18n\Date;
use Exception;

/**
 * CustomerProposals Controller
 *
 * The one proposal put to a customer, and the envelope the papers of their contracts go out in.
 * It is drawn up, sent and signed here, and applying what its parts ask for happens here too.
 *
 * @property \App\Model\Table\CustomerProposalsTable $CustomerProposals
 */
class CustomerProposalsController extends AppController
{
    /**
     * The box an administrator ticks to have a line whose billing has gone passed over.
     */
    public const LEAVE_OUT_WHAT_IS_GONE = 'leave_out_what_is_gone';

    /**
     * The box an administrator ticks to write down an exchange that has already taken place.
     */
    public const CATCHING_UP = 'catching_up';

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
        'applyChanges',
    ];

    /**
     * @var list<string>
     */
    protected array $nestingAutoAdd = [
        'view',
        'edit',
        'conclude',
        'send',
        'applyChanges',
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
            $this->Flash->warning(__('This proposal can no longer be changed.'));

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

            return $this->onFromTheStep((string)$id);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $said = [
                'sent_date' => $this->request->getData('sent_date'),
                'delivery_type' => $this->request->getData('delivery_type'),
            ];
            // A paper that is only handed over is done with once it has been, so the day it went out
            // is the day it stopped being work - which is what everything that asks reads.
            if (!$proposal->expects(ProposalStep::Signed)) {
                $said['conclusion_date'] = $said['sent_date'];
            }

            $proposal = $this->CustomerProposals->patchEntity($proposal, $said, ['validate' => 'sent']);

            if ((new WholeProposal())->recordStep($proposal, ProposalStep::Delivered)) {
                $this->Flash->success(__('The proposal has been recorded as sent.'));

                return $this->onFromTheStep((string)$proposal->id);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The sending could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->set('deliveryTypes', DocumentsDeliveryType::options());
        $this->set('alsoInTheProposal', (new WholeProposal())->whateverTheStepReaches(
            $proposal->id,
            ProposalStep::Delivered,
        ));

        return null;
    }

    /**
     * Records the day the customer signed.
     *
     * What the parts ask for is applied afterwards, which is a step of its own.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function conclude(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers', 'ContractProposals']);

        if ($proposal->hasBeenRevoked()) {
            $this->Flash->warning(__('This customer proposal has been revoked.'));

            return $this->onFromTheStep((string)$id);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $said = ['conclusion_date' => $this->request->getData('conclusion_date')];
            $proposal = $this->CustomerProposals->patchEntity($proposal, $said, ['validate' => 'signed']);

            if ((new WholeProposal())->recordStep($proposal, ProposalStep::Signed)) {
                $this->Flash->success(__('The signature has been recorded.'));

                return $this->onFromTheStep((string)$proposal->id);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The signature could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->set('alsoInTheProposal', (new WholeProposal())->whateverTheStepReaches(
            $proposal->id,
            ProposalStep::Signed,
        ));

        return null;
    }

    /**
     * The way on once there is nothing more to do here, closing the window if this is one.
     *
     * A step is one errand run from the middle of a table, so the table stays where it was and the
     * errand opens a window of its own. Afterwards that window has nothing left to show, and one
     * left standing is one the reader has to close by hand. The proposal is shown first all the same:
     * what they have just said, or why there was nothing to say, before the window goes.
     *
     * Taken by every way out of a step, not only the ones that wrote something - papers already
     * settled and a proposal with nothing left to apply end the errand just as surely, and are the
     * likeliest thing to be clicked from a table that has been open for a while.
     *
     * The window says of itself that it is one, and the URL filter carries that along, so nothing
     * has to be threaded through the form: it is enough to ask the request.
     *
     * @param string $id Which proposal.
     * @return \Cake\Http\Response|null
     */
    private function onFromTheStep(string $id): ?Response
    {
        $url = ['action' => 'view', $id];

        if ($this->getRequest()->getQuery('win-link') === 'true') {
            $url['?'] = ['auto-close' => 'true'];
        }

        return $this->redirect($url);
    }

    /**
     * Applies what the whole proposal asks for to the live records.
     *
     * Spelled out a contract at a time, because that is what each part asks about and what each
     * one would write - one merged list would say what is happening to nothing in particular.
     *
     * All or none. A package half written into the records is the worst of both: nothing says
     * which half, and the papers it came from read as settled either way.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when applied, renders the preview otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function applyChanges(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers', 'ContractProposals']);

        if ($proposal->hasBeenRevoked()) {
            $this->Flash->warning(__('This customer proposal has been revoked.'));

            return $this->onFromTheStep((string)$id);
        }

        $whole = new WholeProposal();
        $parts = $whole->dueToBeApplied(
            (string)$proposal->id,
            (array)($this->request->getData('handed_over_on') ?? []),
        );

        if ($parts === []) {
            $this->Flash->warning(__('There are no changes left to apply in this proposal.'));

            return $this->onFromTheStep((string)$id);
        }

        // Two questions, not one. Whether the page still has a form to show asks what this reader
        // could allow; whether the button may write asks what they actually did allow. Rolled into
        // one, the form would be hidden from the only person who can unhide it.
        $leaveOut = $whole->anythingIsGone($parts) && $this->mayLeaveLinesOut();
        $leavingOut = $leaveOut && $this->request->getData(self::LEAVE_OUT_WHAT_IS_GONE) == '1';
        $stopped = $whole->anythingStops($parts, $leaveOut);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($whole->anythingStops($parts, $leavingOut)) {
                $this->Flash->error(__('The changes of this proposal cannot be applied in its'
                    . ' current state.'));
            } elseif ($whole->anyHandoverIsStillToCome($parts)) {
                $this->Flash->error(__('Borrowed equipments cannot be recorded as handed over on a'
                    . ' day still to come.'));
            } elseif ($this->applyTheWholePackage($parts, $leavingOut)) {
                return $this->onFromTheStep((string)$id);
            }
        }

        $this->set('customerProposal', $proposal);
        $this->set('parts', $parts);
        $this->set('stopped', $stopped);
        $this->set('closed_period_override', $this->mayReachIntoClosedPeriods());
        $this->set('below_minimum_override', $this->mayGoBelowMinimum());
        // Offered only where it is needed, and only to somebody who may answer for it.
        $this->set('leave_out_override', $leaveOut);
        $this->set('catching_up_override', $this->mayOverride() && array_filter(
            $parts,
            fn(array $part): bool => !$part['papers']->purpose->needsAVersion(),
        ) !== []);

        return null;
    }

    /**
     * Writes every part of the package, or none of it.
     *
     * @param array<array<string, mixed>> $parts What is to be applied.
     * @param bool $leavingOut Whether a line whose billing has gone is passed over and written
     *   down rather than stopping the whole package.
     * @return bool
     */
    private function applyTheWholePackage(array $parts, bool $leavingOut = false): bool
    {
        try {
            (new WholeProposal())->apply(
                parts: $parts,
                by: $this->getRequest()->getAttribute('identity')['id'] ?? null,
                reach_into_closed_periods: $this->mayReachIntoClosedPeriods()
                    && $this->request->getData(BillingsTable::ALLOW_CLOSED_PERIODS) == '1',
                go_below_minimum: $this->mayGoBelowMinimum()
                    && $this->request->getData(BillingsTable::ALLOW_BELOW_MINIMUM) == '1',
                leave_out_what_is_gone: $leavingOut,
                catching_up: $this->mayOverride() && $this->request->getData(self::CATCHING_UP) == '1',
            );
        } catch (Exception $failure) {
            $this->Flash->error(__(
                'The changes of the proposal could not be applied: {0}',
                $failure->getMessage(),
            ));

            return false;
        }

        $this->Flash->success(__n(
            'The changes of the proposal have been applied to the live records.',
            'The changes of the proposal and everything in it have been applied to the live'
            . ' records.',
            count($parts),
        ));

        foreach ($parts as $part) {
            if ($part['papers']->proposedChanges()->contract->endsTheContract()) {
                $this->Flash->warning(__(
                    'Contract {0} now has an end date. Its state was not changed, because a'
                    . ' change of state has requirements of its own.',
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
        $reading = new ProposalReading();
        $said = [];

        foreach ((new WholeProposal())->partsOf((string)$proposal->id) as $part) {
            $said[] = ['papers' => $part] + $reading->of($part);
        }

        return $said;
    }

    /**
     * Whether this request is one that may be offered the way into an invoiced period at all.
     *
     * Offered to an administrator and a manager and to nobody else, and even there it is a box that
     * has to be ticked - the same gate carrying one contract's papers over has always had.
     *
     * @return bool
     */
    private function mayReachIntoClosedPeriods(): bool
    {
        return $this->mayOverride();
    }

    /**
     * Whether this request may pass over a line whose billing is no longer on the contract.
     *
     * An administrator or a manager and nobody else, ticking a box for it. What is passed over is
     * somebody's to see to afterwards, and a signed proposal is not something to be settled by
     * halves without deciding to.
     *
     * @return bool
     */
    private function mayLeaveLinesOut(): bool
    {
        return $this->mayOverride();
    }

    /**
     * Gives up on the papers, and on the papers of the contracts they hold.
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

        $by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->CustomerProposals->giveUpOnTheProposal($proposal, is_string($by) ? $by : null)) {
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
     * Afterwards the reader is on the customer's workbench, where the rest of their papers are.
     * What could not be deleted leaves them on the proposal.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects to the workbench, or back to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $proposal = $this->CustomerProposals->get($id, contain: ['ContractProposals']);

        if (!$this->CustomerProposals->mayBeDeleted($proposal)) {
            $this->Flash->warning(__('This proposal can no longer be deleted.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if (!$this->CustomerProposals->delete($proposal)) {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be deleted. Please, try again.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $this->Flash->success(__('The proposal has been deleted.'));

        return $this->redirect([
            'controller' => 'Documents',
            'action' => 'manage',
            'customer_id' => $proposal->customer_id,
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
