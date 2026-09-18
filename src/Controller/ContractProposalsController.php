<?php
declare(strict_types=1);

namespace App\Controller;

use App\Contracts\MinimumConnectionPrice;
use App\Contracts\Proposal\ChangePlan;
use App\Contracts\Proposal\PlannedChange;
use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalForm;
use App\Contracts\Proposal\ProposalProjection;
use App\Contracts\Proposal\ProposalSnapshotBuilder;
use App\Contracts\Proposal\ProposedBilling;
use App\Contracts\Proposal\ProposedBillingForm;
use App\Contracts\Proposal\ProposedVersion;
use App\Contracts\Proposal\ReadinessChecks;
use App\Contracts\TheUsualTerm;
use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\ProposalPurpose;
use App\Service\ContractPrint\ContractDocuments;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Exception;

/**
 * ContractProposals Controller
 *
 * @property \App\Model\Table\ContractProposalsTable $ContractProposals
 */
class ContractProposalsController extends AppController
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
        'refreshSnapshot',
        'billingLine',
    ];

    /**
     * @var list<string>
     */
    protected array $nestingAutoAdd = [
        'view',
        'edit',
        'refreshSnapshot',
        'billingLine',
    ];

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
     * View method
     *
     * @param string|null $id Contract version proposal id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $contractProposal = $this->ContractProposals->get($id, contain: [
            'Contracts' => ['Customers', 'InstallationAddresses', 'ServiceTypes'],
            'ContractVersions',
            'TerminatedContractVersions',
            // The papers say which proposal they go out in, and it names itself by what it holds.
            'CustomerProposals' => ['ContractProposals'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('contractProposal'));
        $this->setProposalViewVars($contractProposal);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $proposal = $this->ContractProposals->newEmptyEntity();

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

        // Drawn up from inside a round, the papers go out in it and speak about the day it does.
        // Both are only what the form opens with - the operator may say otherwise.
        $round = $this->roundAskedFor();

        if ($round !== null) {
            $proposal->set('customer_proposal_id', $round->id);
            $proposal->set('effective_from', $round->effective_from);
        }

        if ($this->request->is('post')) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData());
            $this->endWhatTheContractIsBilledFor($proposal);

            // Changing the contract redraws the form so that its versions and services are the
            // ones that contract has; it is not an attempt to save anything yet.
            if (!$this->isARedraw() && $this->saveProposal($proposal)) {
                // A proposal is read after it is written, not the record it hangs on: what was
                // just said about it is the thing worth seeing, and the way back to the card is
                // on the page.
                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractProposal', $proposal);
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being drawn up.
     * @return void
     */
    private function endWhatTheContractIsBilledFor(ContractProposal $proposal): void
    {
        if ($proposal->purpose !== ProposalPurpose::Termination || !$proposal->endsTheContract()) {
            return;
        }

        $changes = $proposal->proposedChanges();
        $spokenFor = $changes->billingsByBillingId();
        $lines = new ProposedBillingForm();

        foreach ($proposal->stateOfThings()->billings() as $billing_id => $billing) {
            if (isset($spokenFor[(string)$billing_id])) {
                continue;
            }

            // The snapshot holds everything the contract has ever billed for, and something that
            // stopped years ago has nothing left to end. A line for it would read as a change on
            // the proposal and would be a change in the records.
            $stopped = $billing['billing_until'] ?? null;
            if ($stopped !== null && new Date((string)$stopped) < $proposal->effective_from) {
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
     * The snapshot stands unless the form says otherwise: the operator is working against what
     * they were shown, and says for themselves when they know the contract has moved underneath
     * it. Asked for here rather than on a page of its own, because a fresh reading may want the
     * dates of the version corrected in the same breath.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $proposal = $this->ContractProposals->get($id, contain: [
            'Contracts' => ['ServiceTypes', 'InstallationAddresses'],
            'CustomerProposals',
        ]);

        if (!$this->ContractProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $takeSnapshot = toBool($this->getRequest()->getData('take_the_snapshot_again')) ?? false;

            $proposal = $this->fillFromForm(
                $proposal,
                $this->request->getData(),
                keepSnapshot: !$takeSnapshot,
            );

            // A billing the changes act on may be gone from the new reading - which is the very
            // case somebody asks for one in - and the rule that every line acts on something the
            // snapshot knows would refuse the saving over a table this form does not even show.
            $takenBack = $takeSnapshot ? $this->dropLinesWhoseBillingIsGone($proposal) : 0;

            if (!$this->isARedraw() && $this->saveProposal($proposal)) {
                $this->sayWhatWasTakenBack($takenBack);

                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractProposal', $proposal);
        $this->setFormViewVars($proposal);

        return null;
    }

    /**
     * Takes the snapshot again, and nothing else.
     *
     * The common case is a button: the contract moved underneath the papers and nothing about them
     * changes. Where the version's dates want correcting in the same breath, the box on the edit
     * form does both - which is also where a bookmark to this address still arrives.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal, or to the form when asked by GET.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function refreshSnapshot(?string $id = null): ?Response
    {
        if (!$this->request->is('post')) {
            return $this->redirect(['action' => 'edit', $id]);
        }

        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$this->ContractProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $contract = $this->contractFor((string)$proposal->contract_id);
        $terminates = $proposal->terminates_contract_version_id;
        $version = match (true) {
            $contract === null => null,
            $proposal->contract_version_id !== null => $this->versionFor($proposal->contract_version_id),
            default => $this->versionToCome($contract->id, [
                'effective_from' => $proposal->effective_from?->toDateString(),
                'changes' => $proposal->changes,
            ]),
        };

        if ($contract === null || $version === null) {
            $this->Flash->error(__('Choose which contract and which version of it this contract'
                . ' proposal is for.'));

            return $this->redirect(['action' => 'edit', $id]);
        }

        $proposal->set('snapshot', (new ProposalSnapshotBuilder())->take(
            $contract,
            $version,
            $terminates === null ? null : $this->versionFor($terminates),
        ));
        $proposal->set('snapshot_taken', DateTime::now());
        $takenBack = $this->dropLinesWhoseBillingIsGone($proposal);

        if (!$this->saveProposal($proposal)) {
            // A fresh reading may raise a question nobody has answered yet, and the form is where
            // it is asked - with the box that takes the snapshot again in the same submission.
            return $this->redirect(['action' => 'edit', $id]);
        }

        $this->sayWhatWasTakenBack($takenBack);

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Tells the operator how many lines a fresh snapshot took back, where it took any.
     *
     * @param int $takenBack How many.
     * @return void
     */
    private function sayWhatWasTakenBack(int $takenBack): void
    {
        if ($takenBack === 0) {
            return;
        }

        $this->Flash->warning(__n(
            'One line asked about a billing that is no longer on the contract and has'
            . ' been taken back.',
            '{0} lines asked about billings that are no longer on the contract and have'
            . ' been taken back.',
            $takenBack,
            $takenBack,
        ));
    }

    /**
     * Takes back the lines that ask about a billing the new snapshot no longer knows.
     *
     * Something moving underneath the proposal is why anybody asks for a fresh snapshot in the
     * first place, so a line left pointing at a billing that is gone would only have the saving
     * refused over a table this form does not even show.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal and its new snapshot.
     * @return int How many lines were taken back.
     */
    private function dropLinesWhoseBillingIsGone(ContractProposal $proposal): int
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

        if (!$proposal instanceof ContractProposal) {
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
        $written = null;
        $refused = null;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $service_id = $data['service_id'] ?? null;

            $written = $form->read(
                $data + ['billing_id' => $replaces],
                $edited,
                $this->chosenService(is_string($service_id) ? $service_id : null),
                $this->isAdmin(),
            );

            $refused = $this->belowTheMinimum($proposal, $changes->withLine($written), $written);

            if ($refused !== null) {
                $this->Flash->error($refused);
            } elseif ($this->saveChanges($proposal, $changes->withLine($written))) {
                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractProposal', $proposal);
        $this->set('line', $edited);
        $this->set('replaced', $replaced);
        // a refused line comes back as it was typed, not as it was before
        $this->set('values', $written?->toArray() ?? $form->fill($edited, $replaced));
        $this->set('below_minimum_override', $this->isAdmin());
        $this->set('below_minimum_refused', $refused);
        $this->set('services', $this->servicesFor($proposal, [
            $edited?->service_id,
            $replaced?->service_id,
        ]));

        return null;
    }

    /**
     * Why the line just written may not stand, where it prices the connection below the minimum.
     *
     * Asked here because this is the one place a priced line is written, and of that line alone:
     * the lines already standing were asked when they were written, and a minimum raised since is
     * the preview of the changes's to say. What guards the records is the billing itself when the
     * proposal is applied - this only saves the operator finding out there.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What it would ask for with the line in it.
     * @param \App\Contracts\Proposal\ProposedBilling $written The line just written.
     * @return string|null What the operator is told, or null where the line may stand.
     */
    private function belowTheMinimum(
        ContractProposal $proposal,
        ProposalChanges $changes,
        ProposedBilling $written,
    ): ?string {
        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable('Billings');
        $minimum = $billings->minimumConnectionPriceOf($proposal->contract_id);

        if ($minimum === null) {
            return null;
        }

        // The proposal as it would read with the line in it, without touching the one being edited.
        $withTheLine = clone $proposal;
        $withTheLine->set('changes', $changes->toArray());

        $below = MinimumConnectionPrice::linesBelow(
            $withTheLine,
            $minimum,
            fn(ProposedBilling $line): bool => $line->id === $written->id,
        );

        return $below === [] ? null : MinimumConnectionPrice::refusal($minimum);
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

        if (!$proposal instanceof ContractProposal) {
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

        if (!$proposal instanceof ContractProposal) {
            return $proposal;
        }

        $this->saveChanges($proposal, $proposal->proposedChanges()->withoutLine((string)$line));

        return $this->redirect(['action' => 'view', $proposal->id]);
    }

    /**
     * The proposal, if it is one that may still be changed.
     *
     * @param string|null $id Contract version proposal id.
     * @return \App\Model\Entity\ContractProposal|\Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function openProposal(?string $id): ContractProposal|Response|null
    {
        $proposal = $this->ContractProposals->get($id, contain: [
            'Contracts' => ['ServiceTypes', 'InstallationAddresses'],
            'CustomerProposals',
        ]);

        if ($this->ContractProposals->mayBeEdited($proposal)) {
            return $proposal;
        }

        $this->Flash->error(__('This proposal can no longer be changed.'));

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Saves what the proposal now asks for.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What it asks for.
     * @return bool
     */
    private function saveChanges(ContractProposal $proposal, ProposalChanges $changes): bool
    {
        $proposal = $this->ContractProposals->patchEntity($proposal, [
            'changes' => $changes->toArray(),
        ]);

        if ($this->ContractProposals->save($proposal)) {
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param string $billing_id Which billing.
     * @return \App\Model\Entity\Billing|null
     */
    private function billingOnTheContract(
        ContractProposal $proposal,
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
     * What is no longer sold is left out, the same as on a billing added to a contract - a proposal
     * is where new arrangements are made, and offering a tariff nobody may take is offering to make
     * a mistake.
     *
     * Except what is already chosen. Changing a line on a service that has since been retired is
     * exactly what somebody comes to this page for, and a list without it would quietly move them
     * onto another tariff.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param array<int|string|null> $keep Services already chosen, which stay on the list whatever
     *   they are.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Service>
     */
    private function servicesFor(ContractProposal $proposal, array $keep = []): SelectQuery
    {
        $contract = $this->contractFor((string)$proposal->contract_id);
        $keep = array_values(array_filter($keep));

        $query = $this->ContractProposals->Contracts->Billings->Services
            ->find('list', order: ['name'])
            ->where($contract === null ? [] : ['OR' => [
                'Services.service_type_id' => $contract->service_type_id,
                'Services.service_type_id IS' => null,
            ]]);

        $offered = ['Services.currently_offered' => true];

        return $query->where($keep === []
            ? $offered
            : ['OR' => [$offered, ['Services.id IN' => $keep]]]);
    }

    /**
     * Delete method
     *
     * Afterwards the reader is on the proposal put to the customer that held these papers, which
     * is still there. What could not be deleted leaves them on the papers themselves.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects to the proposal that held it, or back to it.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$this->ContractProposals->delete($proposal)) {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be deleted. Please, try again.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $this->Flash->success(__('The proposal has been deleted.'));

        return $this->redirect([
            'controller' => 'CustomerProposals',
            'action' => 'view',
            $proposal->customer_proposal_id,
            'customer_id' => $proposal->customer_proposal->customer_id,
        ]);
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

        $proposal = $this->ContractProposals->get($id, contain: ['CustomerProposals']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $proposal->revoked = DateTime::now();
        $proposal->revoked_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->ContractProposals->save($proposal, ['checkRules' => false])) {
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
     * Which contract the papers are about comes from the form like everything else, while they are
     * being drawn up: the address only fills that field in, so what was chosen there stands even
     * where it is not the contract the form was opened under. Afterwards it is theirs to keep.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param array<string, mixed> $data What the form sent.
     * @param bool $keepSnapshot Whether the snapshot it already has stands.
     * @return \App\Model\Entity\ContractProposal
     */
    private function fillFromForm(
        ContractProposal $proposal,
        array $data,
        bool $keepSnapshot = false,
    ): ContractProposal {
        // The form asking to be drawn again is not an attempt to save, so a half-filled one is
        // expected rather than wrong.
        $redrawing = $this->isARedraw();

        // Papers stay with the contract they were drawn up for, so what arrives about it is read
        // only while they are being drawn. Moving them afterwards would leave the snapshot, the
        // version and the lines of billing speaking about a contract the papers no longer name.
        if (!$proposal->isNew()) {
            unset($data['contract_id']);
        }

        $form = new ProposalForm();
        $purpose = $this->purposeFrom($data, $proposal);
        $data['purpose'] = $purpose->value;

        // What the head of the form asks is laid over what the proposal already asks of the
        // billings - those are edited a line at a time and never travel in this submission.
        $data['changes'] = $form->changesFrom($data, $proposal->isNew()
            ? ProposalChanges::nothing()
            : $proposal->proposedChanges(), $purpose);
        $data['confirmations'] = $form->confirmationsFrom($data);
        $ends = $this->endOfTheVersion($data);

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
            return $this->ContractProposals->patchEntity($proposal, $data, ['validate' => false]);
        }

        // The day the papers take effect is asked for only where they are a change agreed while the
        // version runs, and even there it may be left empty. An ending works it out from the day it
        // ends on, because what is billed for stops the day before the papers apply. Anything else
        // takes effect with its version.
        $said = $data['effective_from'] ?? null;
        $saidNothing = !is_string($said) || trim($said) === '';

        if ($purpose === ProposalPurpose::Termination) {
            if ($ends === null) {
                $proposal = $this->ContractProposals->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError('ends_on', [__('Say which day the service runs to.')]);

                return $proposal;
            }

            $data['effective_from'] = $ends->addDays(1)->toDateString();
        } elseif ($version !== null && ($saidNothing || !$purpose->asksForItsOwnDay())) {
            $data['effective_from'] = $version->valid_from->toDateString();
        }

        if (!$keepSnapshot) {
            $contract = $this->contractFor((string)($data['contract_id'] ?? $proposal->contract_id));
            $terminates = $data['terminates_contract_version_id']
                ?? $proposal->terminates_contract_version_id;
            $terminated = $terminates === null ? null : $this->versionFor((string)$terminates);

            // Papers for a new contract may be about a version that is still to come, and carrying
            // them over is what brings it into being. Anything else is about a version that is
            // already there, so one has to be named.
            $mayStartOne = $purpose->mayStartAVersion();

            if ($contract === null || ($version === null && !$mayStartOne)) {
                // Without those there is nothing to take a snapshot of, and the columns that would
                // hold one are not on the form - so it is said where the operator is looking,
                // with everything they typed still in front of them.
                $proposal = $this->ContractProposals->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError(
                    $contract === null ? 'contract_id' : 'contract_version_id',
                    [__('Choose which contract and which version of it this contract proposal'
                        . ' is for.')],
                );

                return $proposal;
            }

            // A version that is still to come is photographed as it will be, so that the papers
            // print the same either way and the record ends up saying what they said.
            $snapshotted = $version ?? $this->versionToCome($contract->id, $data);

            if ($snapshotted === null) {
                $proposal = $this->ContractProposals->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError(
                    'effective_from',
                    [__('Say which day the new contract starts.')],
                );

                return $proposal;
            }

            $data['snapshot'] = (new ProposalSnapshotBuilder())->take($contract, $snapshotted, $terminated);
            $data['snapshot_taken'] = DateTime::now();
        }

        return $this->ContractProposals->patchEntity($proposal, $data);
    }

    /**
     * What the papers are being drawn up for.
     *
     * The purpose settles what the rest of the submission means, so it is read before any of it.
     * A form that has never named one is drawing up a new contract, which is the common case.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being filled in.
     * @return \App\Model\Enum\ProposalPurpose
     */
    private function purposeFrom(array $data, ContractProposal $proposal): ProposalPurpose
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return bool
     */
    private function saveProposal(ContractProposal $proposal): bool
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
                        $wording[$question] ?? __('Please answer this before the proposal is'
                            . ' sent.'),
                    ]);
                }

                $this->Flash->error(__('The contract is not ready for a contract proposal.'));

                return false;
            }
        }

        $saved = $this->ContractProposals->getConnection()->transactional(
            fn(): bool => $this->giveItAProposalToBePartOf($proposal)
                && (bool)$this->ContractProposals->save($proposal),
        );

        if ($saved) {
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

        $services = $this->ContractProposals->Contracts->Billings->Services
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
        $contract = $this->ContractProposals->Contracts
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
     * The version the papers will bring into being, as they say it will be.
     *
     * Unsaved, and only ever photographed: what actually creates it is applying the proposal.
     * It starts on the day the papers take effect, which for a new contract is the same day said
     * twice, so without that day there is nothing to draw.
     *
     * @param string $contract_id Whose version it will be.
     * @param array<string, mixed> $data What the form said.
     * @return \App\Model\Entity\ContractVersion|null
     */
    private function versionToCome(string $contract_id, array $data): ?ContractVersion
    {
        $said = $data['effective_from'] ?? null;

        if (!is_string($said) || trim($said) === '') {
            return null;
        }

        $asked = ProposedVersion::fromArray((array)($data['changes']['version'] ?? []));

        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->ContractProposals->ContractVersions->newEmptyEntity();

        $version->set('contract_id', $contract_id);
        $version->set('valid_from', new Date($said));
        $version->set('valid_until', $asked->names('valid_until') ? $asked->get('valid_until') : null);
        $version->set(
            'obligation_until',
            $asked->names('obligation_until') ? $asked->get('obligation_until') : null,
        );

        return $version;
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
        $version = $this->ContractProposals->ContractVersions
            ->find()
            ->where(['ContractVersions.id' => $id])
            ->first();

        return $version;
    }

    /**
     * What the form needs to draw itself.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return void
     */
    private function setFormViewVars(ContractProposal $proposal): void
    {
        $contract = $this->contractFor(
            (string)($proposal->contract_id ?? $this->contract_id ?? $this->named('contract_id') ?? ''),
        );

        // Named the way every other form names a contract: its number, the service and where it is.
        $contracts = $this->ContractProposals->Contracts->find(
            'list',
            contain: ['InstallationAddresses', 'ServiceTypes'],
            order: ['Contracts.number'],
        );
        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }

        $versions = $this->ContractProposals->ContractVersions
            ->find('list', valueField: 'name')
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

        // The day a minimum term usually runs to, offered rather than typed. Counted from the day
        // the papers take effect, which is what the operator has said by now or the version says.
        $takesEffect = $proposal->effective_from ?? $effectiveFromDefault;
        $obligationOffered = $takesEffect === null ? null : TheUsualTerm::from($takesEffect);

        $purpose = $proposal->purpose ?? ProposalPurpose::NewContract;
        $purposes = ProposalPurpose::options();

        // The envelopes of this customer that are still open, so papers drawn up now can go out
        // with whatever else is already waiting to.
        $rounds = $this->openRoundsOf($contract->customer_id ?? $this->customer_id);

        // And what a proposal drawn up here and now would ask of the customer themselves, which
        // is most often nothing: the papers of the contract are the point of it.
        $roundPurposes = CustomerProposalPurpose::options();

        $this->set(compact(
            'contracts',
            'versions',
            'questions',
            'contractNumbers',
            'purpose',
            'purposes',
            'rounds',
            'roundPurposes',
            'effectiveFromDefault',
            'obligationOffered',
        ));
        $this->set('wording', ReadinessChecks::wording());
        $this->set('deliveryMethods', $this->deliveryMethodOptions());
    }

    /**
     * Makes sure the papers are part of a proposal, drawing one up where they are not.
     *
     * There is one proposal and it is put to the customer; what it does for a contract is a part
     * of it. Papers drawn up from the contract rather than from a proposal get one of their own,
     * so that nothing ever stands outside one - and the form says what that one asks of the
     * customer themselves, which is most often nothing at all.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The papers.
     * @return bool
     */
    private function giveItAProposalToBePartOf(ContractProposal $proposal): bool
    {
        if ($proposal->customer_proposal_id !== null) {
            return true;
        }

        $contract = $this->ContractProposals->Contracts
            ->find()
            ->where(['Contracts.id' => $proposal->contract_id])
            ->first();

        if ($contract === null) {
            return true;
        }

        $rounds = $this->ContractProposals->CustomerProposals;
        $round = $rounds->newEmptyEntity();

        $round->set('customer_id', $contract->customer_id);
        $round->set('effective_from', $proposal->effective_from);
        $round->set('purpose', CustomerProposalPurpose::tryFrom(
            (string)$this->getRequest()->getData('new_round_purpose'),
        ));

        if (!$rounds->save($round)) {
            $proposal->setError('customer_proposal_id', [
                __('This contract proposal could not be given a customer proposal to be part of.'),
            ]);

            return false;
        }

        $proposal->set('customer_proposal_id', $round->id);

        return true;
    }

    /**
     * The round the form was opened from, where it was opened from one.
     *
     * @return \App\Model\Entity\CustomerProposal|null
     */
    private function roundAskedFor(): ?CustomerProposal
    {
        $id = $this->named('customer_proposal_id') ?? $this->getRequest()->getQuery('proposal_id');

        if (!is_string($id) || $id === '') {
            return null;
        }

        /** @var \App\Model\Entity\CustomerProposal|null $round */
        $round = $this->ContractProposals->CustomerProposals
            ->find()
            ->where(['CustomerProposals.id' => $id])
            ->first();

        return $round;
    }

    /**
     * The customer's rounds that have not been settled, as a list to pick from.
     *
     * @param string|null $customer_id Whose rounds.
     * @return array<string, string>
     */
    private function openRoundsOf(?string $customer_id): array
    {
        if ($customer_id === null) {
            return [];
        }

        $rounds = $this->ContractProposals->CustomerProposals
            ->find('open')
            ->contain(['ContractProposals'])
            ->where(['CustomerProposals.customer_id' => $customer_id])
            ->orderByDesc('CustomerProposals.effective_from');

        $found = [];

        foreach ($rounds as $round) {
            $found[(string)$round->id] = sprintf(
                '%s - %s',
                $round->effective_from,
                $round->whatItIsFor(),
            );
        }

        return $found;
    }

    /**
     * What the detail of a proposal needs to draw itself.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return void
     */
    private function setProposalViewVars(ContractProposal $proposal): void
    {
        $changes = $proposal->proposedChanges();
        $snapshot = $proposal->stateOfThings();

        $this->set('confirmations', $proposal->confirmations());
        // What would be billed for, each row saying where it comes from - the same projection the
        // documents print from, so the table and the paper cannot disagree.
        $this->set('rows', (new ProposalProjection())->explain(
            $snapshot->hydrate()->billings,
            $changes,
            $proposal->effective_from,
            $snapshot->servicesChosenBy($changes),
        ));
        $this->set('mayBeEdited', $this->ContractProposals->mayBeEdited($proposal));
        $this->set('mayBeDeleted', $this->ContractProposals->mayBeDeleted($proposal));
        $this->set('deliveryMethods', $this->deliveryMethodOptions());
        // Only the count: the table itself is drawn by a cell, which asks for what it draws.
        $this->set('filed', (new ContractDocuments())->filedAgainst([$proposal])[$proposal->id] ?? []);
        // Only what the proposal asks for. The rest of what applying it would write is worked
        // out against the records as they stand today, so it means something on the preview, where
        // it is about to happen, and nothing here.
        $this->set('planned', array_values(array_filter(
            (new ChangePlan())->of($proposal),
            fn(PlannedChange $one): bool => $one->asked,
        )));
    }

    /**
     * The ways papers can go out to a customer.
     *
     * @return array<int|string, string>
     */
    private function deliveryMethodOptions(): array
    {
        return DocumentsDeliveryType::options();
    }

    /**
     * Whether whoever is asking is trusted with the whole application.
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        return ($this->getRequest()->getAttribute('identity')['role'] ?? null) === 'admin';
    }
}
