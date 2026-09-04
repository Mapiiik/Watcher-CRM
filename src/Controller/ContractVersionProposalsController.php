<?php
declare(strict_types=1);

namespace App\Controller;

use App\Contracts\Proposal\ProposalForm;
use App\Contracts\Proposal\ProposalSnapshotBuilder;
use App\Contracts\Proposal\ProposalTransfer;
use App\Contracts\Proposal\ReadinessChecks;
use App\Contracts\Proposal\TransferPreview;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractDeliveryMethod;
use App\Model\Table\BillingsTable;
use Cake\Http\Response;
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
        $show_settled = (bool)$request->getQuery('show_settled');

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

        if ($this->contract_id !== null) {
            $proposal->contract_id = $this->contract_id;
        }

        // Drawn up from a version's own page, the version it is for is already settled.
        $named = $this->getRequest()->getQuery('contract_version_id');
        if (is_string($named) && $named !== '') {
            $proposal->contract_version_id = $named;
        }

        if ($this->request->is('post')) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData());

            if ($this->saveProposal($proposal)) {
                return $this->afterAddRedirect(['action' => 'view', $proposal->id]);
            }
        }

        $this->set('contractVersionProposal', $proposal);
        $this->setFormViewVars($proposal);

        return null;
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
        $proposal = $this->ContractVersionProposals->get($id);

        if (!$this->ContractVersionProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData(), keepSnapshot: true);

            if ($this->saveProposal($proposal)) {
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
        $proposal = $this->ContractVersionProposals->get($id);

        if (!$this->ContractVersionProposals->mayBeEdited($proposal)) {
            $this->Flash->error(__('This proposal can no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->fillFromForm($proposal, $this->request->getData());

            if ($this->saveProposal($proposal)) {
                $this->Flash->success(__('The snapshot has been taken again.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }
        } else {
            $this->Flash->warning(__(
                'Check what the proposal asks for against the state of things as it is now.'
                . ' A billing that is no longer there cannot be changed by it.',
            ));
        }

        $this->set('contractVersionProposal', $proposal);
        $this->setFormViewVars($proposal, fresh: true);

        return null;
    }

    /**
     * Records that the papers went out, which is what settles what stands behind them.
     *
     * @param string|null $id Contract version proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function send(?string $id = null): ?Response
    {
        $proposal = $this->ContractVersionProposals->get($id, contain: ['Contracts']);

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
                        ? __('The proposal has been marked as dealt with; it changed nothing.')
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
        $data = $this->dataWithAdditionalParameters($this->ContractVersionProposals, $data);

        $form = new ProposalForm();
        $data['changes'] = $form->changesFrom($data, $this->chosenServices($data));
        $data['acknowledgements'] = $form->confirmationsFrom($data);

        if (!$keepSnapshot) {
            $contract = $this->contractFor((string)($data['contract_id'] ?? $proposal->contract_id));
            $version = $this->versionFor(
                (string)($data['contract_version_id'] ?? $proposal->contract_version_id),
            );
            $terminates = $data['terminates_contract_version_id']
                ?? $proposal->terminates_contract_version_id;
            $terminated = $terminates === null ? null : $this->versionFor((string)$terminates);

            if ($contract !== null && $version !== null) {
                $data['snapshot'] = (new ProposalSnapshotBuilder())->take($contract, $version, $terminated);
                $data['snapshot_taken'] = DateTime::now();
            }
        }

        return $this->ContractVersionProposals->patchEntity($proposal, $data);
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
        $contract = $this->contractFor((string)$proposal->contract_id);

        if ($contract !== null) {
            $unanswered = (new ReadinessChecks())
                ->unansweredFor($contract, $proposal->confirmations());

            if ($unanswered !== []) {
                foreach (ReadinessChecks::wording() as $question => $wording) {
                    if (in_array($question, $unanswered, true)) {
                        $proposal->setError('acknowledgements', [$question => $wording]);
                    }
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
     * The services the form's lines chose, as they stand now.
     *
     * A line that puts a different service on a billing keeps it with it, because the contract's
     * own snapshot was taken before it was chosen.
     *
     * @param array<string, mixed> $data What the form sent.
     * @return array<string, array<string, mixed>>
     */
    private function chosenServices(array $data): array
    {
        $wanted = [];

        foreach (array_merge((array)($data['lines'] ?? []), (array)($data['additions'] ?? [])) as $line) {
            $id = ((array)$line)['service_id'] ?? null;

            if (is_string($id) && $id !== '') {
                $wanted[$id] = $id;
            }
        }

        if ($wanted === []) {
            return [];
        }

        $services = $this->ContractVersionProposals->Contracts->Billings->Services
            ->find()
            ->contain(['Queues'])
            ->where(['Services.id IN' => array_values($wanted)])
            ->all();

        $chosen = [];
        foreach ($services as $service) {
            $chosen[(string)$service->id] = $service->extract([
                'id', 'name', 'price',
            ]) + ['queue' => $service->queue?->extract([
                'id', 'name', 'caption', 'speed_down', 'speed_up',
                'speed_down_common', 'speed_up_common', 'speed_down_minimum', 'speed_up_minimum',
                'fup_limit', 'data_limit', 'overlimit_fragment', 'overlimit_cost', 'cto_category',
            ])];
        }

        return $chosen;
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
     * @param bool $fresh Whether to show the state of things as it is now rather than as taken.
     * @return void
     */
    private function setFormViewVars(ContractVersionProposal $proposal, bool $fresh = false): void
    {
        $contract = $this->contractFor((string)($proposal->contract_id ?? $this->contract_id ?? ''));

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

        $services = $this->ContractVersionProposals->Contracts->Billings->Services
            ->find('list', order: ['name'])
            ->where($contract === null ? [] : ['OR' => [
                'Services.service_type_id' => $contract->service_type_id,
                'Services.service_type_id IS' => null,
            ]]);

        // What the form lists as the billings to keep, change or end: the state of things as the
        // proposal took it, unless the operator asked to see how it stands now.
        $billings = $fresh || $proposal->isNew()
            ? ($contract->billings ?? [])
            : $proposal->stateOfThings()->hydrate()->billings;

        $questions = $contract === null ? [] : (new ReadinessChecks())->questionsFor($contract);

        // Contracts concluded before the renumbering carry the customer number, one contract to a
        // customer, so both are worth offering and neither is worth looking further than.
        $contractNumbers = $contract === null ? [] : array_values(array_unique(array_filter([
            $contract->number,
            $contract->customer->number ?? null,
        ])));

        $this->set(compact(
            'contract',
            'contracts',
            'versions',
            'services',
            'billings',
            'questions',
            'contractNumbers',
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
        $this->set('changes', $proposal->proposedChanges());
        $this->set('confirmations', $proposal->confirmations());
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
