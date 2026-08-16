<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Settings\Utility\Settings;

/**
 * ContractVersions Controller
 *
 * @property \App\Model\Table\ContractVersionsTable $ContractVersions
 */
class ContractVersionsController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions += ['Contracts.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['ContractVersions.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ContractVersions.note ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $obligations_ending = toBool($this->getRequest()->getQuery('obligations_ending')) ?? false;

        $this->paginate = [
            'order' => $obligations_ending
                ? ['obligation_until' => 'ASC']
                : ['valid_from' => 'DESC'],
        ];
        $query = $this->ContractVersions->find(
            'all',
            contain: [
                'Contracts',
            ],
            conditions: $conditions,
        );

        // the same reading the dashboard card is drawn from, so the card and the listing it
        // points at hold the same versions
        if ($obligations_ending) {
            $query->find('obligationsEnding', within_days: (int)Settings::get(
                'core.dashboard.contracts.obligation_within_days',
                60,
            ));
        }

        $contractVersions = $this->paginate($query);

        $this->set(compact('contractVersions', 'obligations_ending'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract Version id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $contractVersion = $this->ContractVersions->get($id, contain: [
            'Contracts' => [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('contractVersion'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $contractVersion = $this->ContractVersions->newEmptyEntity();

        if ($this->contract_id !== null) {
            $contractVersion->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $contractVersion = $this->ContractVersions->patchEntity(
                $contractVersion,
                $this->dataWithAdditionalParameters($this->ContractVersions, $this->getRequest()->getData()),
            );
            if ($this->ContractVersions->save($contractVersion)) {
                $this->Flash->success(__('The contract version has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $contractVersion->id]);
            }
            $this->Flash->error(__('The contract version could not be saved. Please, try again.'));
        }
        $contracts = $this->ContractVersions->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('contractVersion', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Contract Version id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $contractVersion = $this->ContractVersions->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $contractVersion = $this->ContractVersions->patchEntity($contractVersion, $this->getRequest()->getData());
            if ($this->ContractVersions->save($contractVersion)) {
                $this->Flash->success(__('The contract version has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $contractVersion->id]);
            }
            $this->Flash->error(__('The contract version could not be saved. Please, try again.'));
        }
        $contracts = $this->ContractVersions->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if ($this->customer_id !== null) {
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('contractVersion', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract Version id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $contractVersion = $this->ContractVersions->get($id);
        if ($this->ContractVersions->delete($contractVersion)) {
            $this->Flash->success(__('The contract version has been deleted.'));
        } else {
            $this->flashValidationErrors($contractVersion->getErrors());
            $this->Flash->error(__('The contract version could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
