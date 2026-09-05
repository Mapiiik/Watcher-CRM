<?php
declare(strict_types=1);

namespace App\Controller;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalForm;
use App\Contracts\Proposal\ProposalProjection;
use App\Contracts\Proposal\ProposalSnapshotBuilder;
use App\Contracts\Proposal\ProposalTransfer;
use App\Contracts\Proposal\ProposedBillingForm;
use App\Contracts\Proposal\ReadinessChecks;
use App\Contracts\Proposal\TransferPreview;
use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractDeliveryMethod;
use App\Model\Enum\ProposalPurpose;
use App\Model\Table\BillingsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Exception;

/**
 * ContractVersionProposals Controller
 *
 * @property \App\Model\Table\ContractVersionProposalsTable $ContractVersionProposals
 */
class ContractVersionProposalsController extends AppController
{
    /**
     * What a contract has to be loaded with for a snapshot to be taken of it - the same as printing
     * used to load it, because that is what the documents read.
     *
     * @var array<mixed>
     */
    private const FOR_A_SNAPSHOT = [
        'Billings' => ['Services' => ['Queues']],
        'ContractStates',
        'ContractVersions',
        'Customers' => ['Addresses', 'Emails', 'Phones', 'AccountingProfiles'],
        'InstallationAddresses',
        'IpAddresses',
        'IpNetworks',
        'ServiceTypes',
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
            $conditions += ['Contracts.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['ContractVersionProposals.contract_id' => $this->contract_id];
        }

        $request = $this->getRequest();
        $show_settled = toBool($request->getQuery('show_settled')) ?? false;

        $search = $request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ContractVersionProposals.note ILIKE' => '%' . trim((string)$search) . '%',
                    'ContractVersionProposals.terminated_contract_number ILIKE'
                        => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $query = $this->ContractVersionProposals
            ->find($show_settled ? 'all' : 'open')
            ->contain(['Contracts', 'ContractVersions'])
            ->where($conditions)
            ->orderBy(['ContractVersionProposals.effective_from' => 'DESC']);

        $contractVersionProposals = $this->paginate($query);

        $this->set(compact('contractVersionProposals', 'show_settled'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract version proposal id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $contractVersionProposal = $this->ContractVersionProposals->get($id, contain: [
            'Contracts' => ['Customers', 'InstallationAddresses'],
            'ContractVersions',
            'TerminatedContractVersions',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('contractVersionProposal'));
        $this->setProposalViewVars($contractVersionProposal);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $proposal = $this->ContractVersionProposals->newEmptyEntity();

        // Which contract the papers are for comes from the nested route the form was opened
        // under; a link from a version's own page settles the version as well.
        foreach (['contract_id' => $this->contract_id, 'contract_version_id' => null] as $what => $known) {
            $named = $known ?? $this->named($what);

            if ($named !== null) {
                $proposal->set($what, $named);
            }
        }

        $named = $this->named('purpose');
        $proposal->set('purpose', ProposalPurpose::tryFrom((string)$named) ?? ProposalPurpose::NewContract);

        if ($this->request->is('post')) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData());
            $this->endWhatTheContractIsBilledFor($proposal);

            // Changing the contract redraws the form so that its versions and services are the
            // ones that contract has; it is not an attempt to save anything yet.
            if (!$this->isARedraw() && $this->saveProposal($proposal)) {
                return $this->afterAddRedirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractVersionProposal', $proposal);
        $this->setFormViewVars($proposal);

        return null;
    }

    /**
     * Stops everything the contract is billed for, where a proposal brings the contract to an end.
     *
     * An ending means nothing goes on being charged for, so the lines that say so are put there
     * rather than clicked one at a time. They are the proposal's own lines, no different from any
     * other, so anything that is to run on can be taken back off the proposal's table.
     *
     * A version ending while the contract runs on gets none of this: what is billed for hangs off
     * the contract, which carries on, and the version that follows says what becomes of it.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal being drawn up.
     * @return void
     */
    private function endWhatTheContractIsBilledFor(ContractVersionProposal $proposal): void
    {
        if ($proposal->purpose !== ProposalPurpose::Termination || !$proposal->endsTheContract()) {
            return;
        }

        $changes = $proposal->proposedChanges();
        $spokenFor = $changes->billingsByBillingId();
        $lines = new ProposedBillingForm();

        foreach (array_keys($proposal->stateOfThings()->billings()) as $billing_id) {
            if (isset($spokenFor[(string)$billing_id])) {
                continue;
            }

            $changes = $changes->withLine($lines->ending((string)$billing_id));
        }

        $proposal->set('changes', $changes->toArray());
    }

    /**
     * Whether this request is the form asking to be drawn again rather than to be saved.
     *
     * @return bool
     */
    private function isARedraw(): bool
    {
        return $this->getRequest()->getData('refresh') === 'refresh';
    }

    /**
     * Edit method
     *
     * The snapshot is left where it was: the operator is working against what they were shown, and
     * asks for a fresh one themselves when they know something has moved.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

        if (!$this->ContractVersionProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData(), keepSnapshot: true);

            if (!$this->isARedraw() && $this->saveProposal($proposal)) {
                return $this->afterEditRedirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractVersionProposal', $proposal);
        $this->setFormViewVars($proposal);

        return null;
    }

    /**
     * Takes the snapshot again, and the changes with it.
     *
     * One step rather than two. A billing the changes act on may be gone from the new snapshot -
     * which is the very case somebody asks for a refresh in - and saving the snapshot on its own
     * would then be refused by the rule that every line has to act on something the snapshot knows.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects when done, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function refreshSnapshot(?string $id = null): ?Response
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

        if (!$this->ContractVersionProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData());
            $takenBack = $this->dropLinesWhoseBillingIsGone($proposal);

            if ($this->saveProposal($proposal)) {
                $this->Flash->success($takenBack === 0
                    ? __('The snapshot has been taken again.')
                    : __n(
                        'The snapshot has been taken again. One line asked about a billing that is'
                        . ' no longer on the contract and has been taken back.',
                        'The snapshot has been taken again. {0} lines asked about billings that are'
                        . ' no longer on the contract and have been taken back.',
                        $takenBack,
                        $takenBack,
                    ));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractVersionProposal', $proposal);
        $this->setFormViewVars($proposal);

        return null;
    }

    /**
     * Takes back the lines that ask about a billing the new snapshot no longer knows.
     *
     * Something moving underneath the proposal is why anybody asks for a fresh snapshot in the
     * first place, so a line left pointing at a billing that is gone would only have the saving
     * refused over a table this form does not even show.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal and its new snapshot.
     * @return int How many lines were taken back.
     */
    private function dropLinesWhoseBillingIsGone(ContractVersionProposal $proposal): int
    {
        $snapshot = $proposal->stateOfThings();
        $changes = $proposal->proposedChanges();
        $takenBack = 0;

        foreach ($changes->billings as $line) {
            if ($line->billing_id === null || $snapshot->knowsBilling($line->billing_id)) {
                continue;
            }

            $changes = $changes->withoutLine($line->id);
            $takenBack++;
        }

        if ($takenBack > 0) {
            $proposal->set('changes', $changes->toArray());
        }

        return $takenBack;
    }

    /**
     * Puts a line into the proposal, or changes one that is already there.
     *
     * A line is edited on a page of its own, the same way a billing on a contract is, so that the
     * operator does what they are used to doing. What travels is one line, not a whole table.
     *
     * @param string|null $id Contract version proposal id.
     * @param string|null $line The line being changed; a new one when there is none.
     * @return \Cake\Http\Response|null Redirects when saved, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function billingLine(?string $id = null, ?string $line = null): ?Response
    {
        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractVersionProposal) {
            return $proposal;
        }

        $changes = $proposal->proposedChanges();
        $edited = $line === null ? null : $changes->line($line);

        if ($line !== null && $edited === null) {
            throw new RecordNotFoundException(__('There is no such line on this proposal.'));
        }

        // Changing something that is already billed for starts from what is there.
        $replaces = $edited->billing_id ?? $this->named('replaces');
        $replaced = $replaces === null ? null : $this->billingOnTheContract($proposal, $replaces);

        $form = new ProposedBillingForm();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $service_id = $data['service_id'] ?? null;

            $written = $form->read(
                $data + ['billing_id' => $replaces],
                $edited,
                $this->chosenService(is_string($service_id) ? $service_id : null),
            );

            if ($this->saveChanges($proposal, $changes->withLine($written))) {
                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractVersionProposal', $proposal);
        $this->set('line', $edited);
        $this->set('replaced', $replaced);
        $this->set('values', $form->fill($edited, $replaced));
        $this->set('services', $this->servicesFor($proposal));

        return null;
    }

    /**
     * Stops billing for something, with nothing taking its place.
     *
     * @param string|null $id Contract version proposal id.
     * @param string|null $billing_id The billing that is to stop.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function endBilling(?string $id = null, ?string $billing_id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractVersionProposal) {
            return $proposal;
        }

        $changes = $proposal->proposedChanges();

        // One line to a billing: ending what is already being replaced replaces that line.
        $existing = $changes->billingsByBillingId()[(string)$billing_id] ?? null;
        if ($existing !== null) {
            $changes = $changes->withoutLine($existing->id);
        }

        $this->saveChanges(
            $proposal,
            $changes->withLine((new ProposedBillingForm())->ending((string)$billing_id)),
        );

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * Takes a line back out of the proposal, leaving whatever it acted on as it was.
     *
     * @param string|null $id Contract version proposal id.
     * @param string|null $line Which line.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function dropBillingLine(?string $id = null, ?string $line = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $proposal = $this->openProposal($id);

        if (!$proposal instanceof ContractVersionProposal) {
            return $proposal;
        }

        $this->saveChanges($proposal, $proposal->proposedChanges()->withoutLine((string)$line));

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * The proposal, if it is one that may still be changed.
     *
     * @param string|null $id Contract version proposal id.
     * @return \App\Model\Entity\ContractVersionProposal|\Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function openProposal(?string $id): ContractVersionProposal|Response|null
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

        if ($this->ContractVersionProposals->mayBeEdited($proposal)) {
            return $proposal;
        }

        $this->Flash->error(__('This proposal can no longer be changed.'));

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Saves what the proposal now asks for.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What it asks for.
     * @return bool
     */
    private function saveChanges(ContractVersionProposal $proposal, ProposalChanges $changes): bool
    {
        $proposal = $this->ContractVersionProposals->patchEntity($proposal, [
            'changes' => $changes->toArray(),
        ]);

        if ($this->ContractVersionProposals->save($proposal)) {
            $this->Flash->success(__('The proposal has been saved.'));

            return true;
        }

        $this->flashValidationErrors($proposal->getErrors());
        $this->Flash->error(__('The proposal could not be saved. Please, try again.'));

        return false;
    }

    /**
     * One of the billings the proposal's snapshot took down.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param string $billing_id Which billing.
     * @return \App\Model\Entity\Billing|null
     */
    private function billingOnTheContract(
        ContractVersionProposal $proposal,
        string $billing_id,
    ): ?Billing {
        foreach ($proposal->stateOfThings()->hydrate()->billings as $billing) {
            if ((string)$billing->id === $billing_id) {
                return $billing;
            }
        }

        return null;
    }

    /**
     * The services a line on this proposal may be for.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Service>
     */
    private function servicesFor(ContractVersionProposal $proposal): SelectQuery
    {
        $contract = $this->contractFor((string)$proposal->contract_id);

        return $this->ContractVersionProposals->Contracts->Billings->Services
            ->find('list', order: ['name'])
            ->where($contract === null ? [] : ['OR' => [
                'Services.service_type_id' => $contract->service_type_id,
                'Services.service_type_id IS' => null,
            ]]);
    }

    /**
     * Records that the papers went out, which is what settles what stands behind them.
     *
     * It may be recorded again for as long as the proposal is open, because papers do go out
     * again - by another means, or after the first attempt came back. The content stays locked
     * either way; what moves is the day, and with it the deadline for an answer.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function send(?string $id = null): ?Response
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->ContractVersionProposals->patchEntity($proposal, [
                'sent_date' => $this->request->getData('sent_date'),
                'sent_by' => $this->request->getData('sent_by'),
            ]);

            if ($this->ContractVersionProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been recorded as sent.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The sending could not be recorded. Please, try again.'));
        }

        $this->set('contractVersionProposal', $proposal);
        $this->set('deliveryMethods', $this->deliveryMethodOptions());

        return null;
    }

    /**
     * Records the day the customer agreed to the proposal.
     *
     * Kept apart from sending because they are two different events, and because sending settles
     * what stands behind the papers while this does not: a signature may be recorded, corrected and
     * recorded again for as long as the proposal is open. Nothing may be carried over without it.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function conclude(?string $id = null): ?Response
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->ContractVersionProposals->patchEntity($proposal, [
                'conclusion_date' => $this->request->getData('conclusion_date'),
            ]);

            if ($this->ContractVersionProposals->save($proposal)) {
                $this->Flash->success(__('The signature has been recorded.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The signature could not be recorded. Please, try again.'));
        }

        $this->set('contractVersionProposal', $proposal);

        return null;
    }

    /**
     * Shows what carrying the proposal over would do, and does it when told to.
     *
     * This is the one place a proposal touches anything outside itself. Up to here the live records
     * have not moved, which is what lets a proposal nobody signs be given up on with one click.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects when carried over, renders the preview otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function transfer(?string $id = null): ?Response
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

        $preview = new TransferPreview();
        $found = $preview->of($proposal);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($preview->anythingStopsIt($found)) {
                $this->Flash->error(__('This proposal cannot be carried over as it stands.'));
            } else {
                try {
                    (new ProposalTransfer())->carryOver(
                        $proposal,
                        $this->getRequest()->getAttribute('identity')['id'] ?? null,
                        $this->mayReachIntoClosedPeriods()
                            && $this->request->getData(BillingsTable::ALLOW_CLOSED_PERIODS) == '1',
                    );

                    $this->Flash->success($proposal->proposedChanges()->isEmpty()
                        ? __('The proposal has been marked as dealt with. It changed nothing.')
                        : __('The proposal has been carried over into the live records.'));

                    if ($proposal->proposedChanges()->contract->endsTheContract()) {
                        $this->Flash->warning(__(
                            'The contract has been given an end date. Its state is left as it was,'
                            . ' because that has its own requirements to satisfy.',
                        ));
                    }

                    return $this->redirect(['action' => 'view', $proposal->id]);
                } catch (Exception $failure) {
                    $this->Flash->error(__(
                        'The proposal could not be carried over: {0}',
                        $failure->getMessage(),
                    ));
                }
            }
        }

        $this->set('contractVersionProposal', $proposal);
        $this->set('found', $found);
        $this->set('stopped', $preview->anythingStopsIt($found));
        $this->set('billingsNow', $preview->billingsNow($proposal));
        $this->set('billingsAfterwards', $preview->billingsAfterwards($proposal));
        $this->set('closed_period_override', $this->mayReachIntoClosedPeriods());

        return null;
    }

    /**
     * Whether this request is one that may be offered the way into an invoiced period at all.
     *
     * Offered to an administrator and to nobody else, and even there it is a box that has to be
     * ticked - the same gate the service change has had.
     *
     * @return bool
     */
    private function mayReachIntoClosedPeriods(): bool
    {
        return ($this->getRequest()->getAttribute('identity')['role'] ?? null) === 'admin';
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $proposal = $this->ContractVersionProposals->get($id);

        if ($this->ContractVersionProposals->delete($proposal)) {
            $this->Flash->success(__('The proposal has been deleted.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Gives up on the proposal, which touches nothing else - the live records never moved.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function revoke(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->ContractVersionProposals->get($id);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $proposal->revoked = DateTime::now();
        $proposal->revoked_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->ContractVersionProposals->save($proposal, ['checkRules' => false])) {
            $this->Flash->success(__('The proposal has been revoked.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be revoked. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Puts what the form said onto the proposal, taking a snapshot where one is wanted.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param array<string, mixed> $data What the form sent.
     * @param bool $keepSnapshot Whether the snapshot it already has stands.
     * @return \App\Model\Entity\ContractVersionProposal
     */
    private function fillFromForm(
        ContractVersionProposal $proposal,
        array $data,
        bool $keepSnapshot = false,
    ): ContractVersionProposal {
        // The form asking to be drawn again is not an attempt to save, so a half-filled one is
        // expected rather than wrong.
        $redrawing = $this->isARedraw();

        $data = $this->dataWithAdditionalParameters($this->ContractVersionProposals, $data);

        $form = new ProposalForm();
        $purpose = $this->purposeFrom($data, $proposal);
        $data['purpose'] = $purpose->value;

        // What the head of the form asks is laid over what the proposal already asks of the
        // billings - those are edited a line at a time and never travel in this submission.
        $data['changes'] = $form->changesFrom($data, $proposal->isNew()
            ? ProposalChanges::nothing()
            : $proposal->proposedChanges(), $purpose);
        $data['confirmations'] = $form->confirmationsFrom($data);

        // The form's own fields have been read by now, and the marshaller must not see them: it
        // would take a name it shares with an association for a record of its own.
        unset(
            $data['version_change'],
            $data['version_change_named'],
            $data['ends_on'],
            $data['version_only'],
            $data['refresh'],
        );

        $version = $this->versionFor(
            (string)($data['contract_version_id'] ?? $proposal->contract_version_id),
        );

        // A form asking to be drawn again is not an attempt to save, so nothing is held against it
        // - and the day is left exactly as it was typed. Filling it in here is what would make the
        // day of one version stay behind in the field after another was chosen.
        if ($redrawing) {
            return $this->ContractVersionProposals->patchEntity($proposal, $data, ['validate' => false]);
        }

        // The day the papers take effect is asked for only where they are a change agreed while the
        // version runs, and even there it may be left empty. An ending works it out from the day it
        // ends on, because what is billed for stops the day before the papers apply; anything else
        // takes effect with its version.
        $ends = $this->endOfTheVersion($data);
        $said = $data['effective_from'] ?? null;
        $saidNothing = !is_string($said) || trim($said) === '';

        if ($purpose === ProposalPurpose::Termination) {
            if ($ends !== null) {
                $data['effective_from'] = $ends->addDays(1)->toDateString();
            }
        } elseif ($version !== null && ($saidNothing || !$purpose->asksForItsOwnDay())) {
            $data['effective_from'] = $version->valid_from->toDateString();
        }

        if (!$keepSnapshot) {
            $contract = $this->contractFor((string)($data['contract_id'] ?? $proposal->contract_id));
            $terminates = $data['terminates_contract_version_id']
                ?? $proposal->terminates_contract_version_id;
            $terminated = $terminates === null ? null : $this->versionFor((string)$terminates);

            if ($contract === null || $version === null) {
                // Without both there is nothing to take a snapshot of, and the columns that would
                // hold one are not on the form - so it is said where the operator is looking,
                // with everything they typed still in front of them.
                $proposal = $this->ContractVersionProposals->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError(
                    $contract === null ? 'contract_id' : 'contract_version_id',
                    [__('Choose which contract and which version of it these papers are for.')],
                );

                return $proposal;
            }

            $data['snapshot'] = (new ProposalSnapshotBuilder())->take($contract, $version, $terminated);
            $data['snapshot_taken'] = DateTime::now();
        }

        return $this->ContractVersionProposals->patchEntity($proposal, $data);
    }

    /**
     * What the papers are being drawn up for.
     *
     * The purpose settles what the rest of the submission means, so it is read before any of it.
     * A form that has never named one is drawing up a new contract, which is the common case.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal being filled in.
     * @return \App\Model\Enum\ProposalPurpose
     */
    private function purposeFrom(array $data, ContractVersionProposal $proposal): ProposalPurpose
    {
        $said = $data['purpose'] ?? null;

        if (is_string($said) && ProposalPurpose::tryFrom($said) !== null) {
            return ProposalPurpose::from($said);
        }

        return $proposal->purpose ?? ProposalPurpose::NewContract;
    }

    /**
     * The day an ending says the version stops being valid on, as the form gave it.
     *
     * @param array<string, mixed> $data What the form sent.
     * @return \Cake\I18n\Date|null
     */
    private function endOfTheVersion(array $data): ?Date
    {
        $day = $data['ends_on'] ?? null;

        if (!is_string($day) || trim($day) === '') {
            return null;
        }

        try {
            return new Date(trim($day));
        } catch (Exception) {
            return null;
        }
    }

    /**
     * One of the things a link may have settled before the form was opened.
     *
     * @param string $what Which one.
     * @return string|null
     */
    private function named(string $what): ?string
    {
        $named = $this->getRequest()->getQuery($what);

        return is_string($named) && $named !== '' ? $named : null;
    }

    /**
     * Saves the proposal, having first asked the contract whether it is ready to have papers drawn
     * up for it at all.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return bool
     */
    private function saveProposal(ContractVersionProposal $proposal): bool
    {
        if ($proposal->getErrors() !== []) {
            $this->flashValidationErrors($proposal->getErrors());

            return false;
        }

        $contract = $this->contractFor((string)$proposal->contract_id);

        if ($contract !== null) {
            $unanswered = (new ReadinessChecks())
                ->unansweredFor($contract, $proposal->confirmations());

            if ($unanswered !== []) {
                // Each answer is a box on the form named after this column, so the complaint lands
                // on the box rather than on nothing the operator can see.
                $wording = ReadinessChecks::wording();

                foreach ($unanswered as $question) {
                    $proposal->setError('confirmations.' . $question, [
                        $wording[$question] ?? __('Please answer this before the papers go out.'),
                    ]);
                }

                $this->Flash->error(__('The contract is not ready for papers to be drawn up.'));

                return false;
            }
        }

        if ($this->ContractVersionProposals->save($proposal)) {
            $this->Flash->success(__('The proposal has been saved.'));

            return true;
        }

        $this->flashValidationErrors($proposal->getErrors());
        $this->Flash->error(__('The proposal could not be saved. Please, try again.'));

        return false;
    }

    /**
     * The service a line chose, as it stands now.
     *
     * A line that puts a different service on a billing keeps it with it, because the contract's
     * own snapshot was taken before it was chosen.
     *
     * @param string|null $id Which service.
     * @return array<string, mixed>|null
     */
    private function chosenService(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $services = $this->ContractVersionProposals->Contracts->Billings->Services
            ->find()
            ->contain(['Queues'])
            ->where(['Services.id' => $id])
            ->first();

        if ($services === null) {
            return null;
        }

        return $services->extract(['id', 'name', 'price'])
            + ['queue' => $services->queue?->extract([
                'id', 'name', 'caption', 'speed_down', 'speed_up',
                'speed_down_common', 'speed_up_common', 'speed_down_minimum', 'speed_up_minimum',
                'fup_limit', 'data_limit', 'overlimit_fragment', 'overlimit_cost', 'cto_category',
            ])];
    }

    /**
     * The contract, loaded with everything a snapshot and the readiness checks read.
     *
     * @param string $id Which contract.
     * @return \App\Model\Entity\Contract|null
     */
    private function contractFor(string $id): ?Contract
    {
        if ($id === '') {
            return null;
        }

        /** @var \App\Model\Entity\Contract|null $contract */
        $contract = $this->ContractVersionProposals->Contracts
            ->find()
            ->contain(self::FOR_A_SNAPSHOT)
            ->contain('BorrowedEquipments.EquipmentTypes', fn(SelectQuery $q): SelectQuery => $q->where([
                'BorrowedEquipments.borrowed_until IS' => null,
            ]))
            ->contain('SoldEquipments.EquipmentTypes', fn(SelectQuery $q): SelectQuery => $q->where([
                'SoldEquipments.date_of_sale IS' => null,
            ]))
            ->where(['Contracts.id' => $id])
            ->first();

        return $contract;
    }

    /**
     * One contract version.
     *
     * @param string $id Which version.
     * @return \App\Model\Entity\ContractVersion|null
     */
    private function versionFor(string $id): ?ContractVersion
    {
        if ($id === '') {
            return null;
        }

        /** @var \App\Model\Entity\ContractVersion|null $version */
        $version = $this->ContractVersionProposals->ContractVersions
            ->find()
            ->where(['ContractVersions.id' => $id])
            ->first();

        return $version;
    }

    /**
     * What the form needs to draw itself.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return void
     */
    private function setFormViewVars(ContractVersionProposal $proposal): void
    {
        $contract = $this->contractFor(
            (string)($proposal->contract_id ?? $this->contract_id ?? $this->named('contract_id') ?? ''),
        );

        $contracts = $this->ContractVersionProposals->Contracts->find('list', order: ['Contracts.number']);
        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }

        $howAVersionReads = function (ContractVersion $version): string {
            return $version->valid_from . ' - ' . ($version->valid_until ?: __('indefinitely'));
        };

        $versions = $this->ContractVersionProposals->ContractVersions
            ->find('list', valueField: $howAVersionReads)
            ->where($contract === null ? ['1 = 0'] : ['ContractVersions.contract_id' => $contract->id])
            ->orderBy(['ContractVersions.valid_from' => 'DESC']);

        $questions = $contract === null ? [] : (new ReadinessChecks())->questionsFor($contract);

        // Contracts concluded before the renumbering carry the customer number, one contract to a
        // customer, so both are worth offering and nothing else ever is - the number on the paper
        // is one of these two, so it is chosen rather than typed.
        $numbers = $contract === null ? [] : array_values(array_unique(array_filter([
            $contract->number,
            $contract->customer->number ?? null,
        ])));
        $contractNumbers = array_combine($numbers, $numbers);

        $version = $this->versionFor((string)$proposal->contract_version_id);

        // The day the field falls back on when it is left empty, so the hint can name it.
        $effectiveFromDefault = $version?->valid_from;

        $purpose = $proposal->purpose ?? ProposalPurpose::NewContract;
        $purposes = ProposalPurpose::options();

        $this->set(compact(
            'contract',
            'contracts',
            'versions',
            'questions',
            'contractNumbers',
            'purpose',
            'purposes',
            'effectiveFromDefault',
        ));
        $this->set('wording', ReadinessChecks::wording());
        $this->set('deliveryMethods', $this->deliveryMethodOptions());
    }

    /**
     * What the detail of a proposal needs to draw itself.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return void
     */
    private function setProposalViewVars(ContractVersionProposal $proposal): void
    {
        $changes = $proposal->proposedChanges();
        $snapshot = $proposal->stateOfThings();

        $this->set('changes', $changes);
        $this->set('confirmations', $proposal->confirmations());
        // What would be billed for, each row saying where it comes from - the same projection the
        // documents print from, so the table and the paper cannot disagree.
        $this->set('rows', (new ProposalProjection())->explain(
            $snapshot->hydrate()->billings,
            $changes,
            $proposal->effective_from,
            $snapshot->servicesChosenBy($changes),
        ));
        $this->set('mayBeEdited', $this->ContractVersionProposals->mayBeEdited($proposal));
        $this->set('mayBeDeleted', $this->ContractVersionProposals->mayBeDeleted($proposal));
        $this->set('deliveryMethods', $this->deliveryMethodOptions());
    }

    /**
     * The ways papers can go out to a customer.
     *
     * @return array<int|string, string>
     */
    private function deliveryMethodOptions(): array
    {
        return ContractDeliveryMethod::options();
    }
}
