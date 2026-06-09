<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * RemovedIpNetworks Controller
 *
 * @property \App\Model\Table\RemovedIpNetworksTable $RemovedIpNetworks
 */
class RemovedIpNetworksController extends AppController
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
            $conditions += ['RemovedIpNetworks.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['RemovedIpNetworks.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'RemovedIpNetworks.ip_network::character varying ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $removedIpNetworks = $this->paginate($this->RemovedIpNetworks->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('removedIpNetworks'));
    }

    /**
     * View method
     *
     * @param string|null $id Removed IP Network id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $removedIpNetwork = $this->RemovedIpNetworks->get($id, contain: [
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
            'Removers',
        ]);

        $this->set(compact('removedIpNetwork'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $removedIpNetwork = $this->RemovedIpNetworks->newEmptyEntity();

        if ($this->customer_id !== null) {
            $removedIpNetwork->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $removedIpNetwork->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $removedIpNetwork = $this->RemovedIpNetworks
                ->patchEntity($removedIpNetwork, $this->getRequest()->getData());

            // TODO - add who and when deleted this
            $removedIpNetwork->removed = DateTime::now();
            $removedIpNetwork->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

            if ($this->RemovedIpNetworks->save($removedIpNetwork)) {
                $this->Flash->success(__('The removed IP network has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $removedIpNetwork->id]);
            }
            $this->Flash->error(__('The removed IP network could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIpNetworks->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->RemovedIpNetworks->Contracts->find(
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
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('removedIpNetwork', 'customers', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Removed IP Network id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $removedIpNetwork = $this->RemovedIpNetworks->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $removedIpNetwork = $this->RemovedIpNetworks
                ->patchEntity($removedIpNetwork, $this->getRequest()->getData());

            if ($this->RemovedIpNetworks->save($removedIpNetwork)) {
                $this->Flash->success(__('The removed IP network has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $removedIpNetwork->id]);
            }
            $this->Flash->error(__('The removed IP network could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIpNetworks->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->RemovedIpNetworks->Contracts->find(
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
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('removedIpNetwork', 'customers', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Removed IP Network id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $removedIpNetwork = $this->RemovedIpNetworks->get($id);
        if ($this->RemovedIpNetworks->delete($removedIpNetwork)) {
            $this->Flash->success(__('The removed IP network has been deleted.'));
        } else {
            $this->flashValidationErrors($removedIpNetwork->getErrors());
            $this->Flash->error(__('The removed IP network could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
