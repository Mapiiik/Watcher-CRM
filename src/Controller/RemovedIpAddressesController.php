<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * RemovedIpAddresses Controller
 *
 * @property \App\Model\Table\RemovedIpAddressesTable $RemovedIpAddresses
 */
class RemovedIpAddressesController extends AppController
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
            $conditions += ['RemovedIpAddresses.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['RemovedIpAddresses.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'RemovedIpAddresses.ip_address::character varying ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $removedIpAddresses = $this->paginate($this->RemovedIpAddresses->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('removedIpAddresses'));
    }

    /**
     * View method
     *
     * @param string|null $id Removed IP Address id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $removedIpAddress = $this->RemovedIpAddresses->get($id, contain: [
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
            'Removers',
        ]);

        $this->set(compact('removedIpAddress'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $removedIpAddress = $this->RemovedIpAddresses->newEmptyEntity();

        if ($this->customer_id !== null) {
            $removedIpAddress->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $removedIpAddress->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $removedIpAddress = $this->RemovedIpAddresses->patchEntity(
                $removedIpAddress,
                $this->dataWithAdditionalParameters($this->RemovedIpAddresses, $this->getRequest()->getData()),
            );

            // TODO - add who and when deleted this
            $removedIpAddress->removed = DateTime::now();
            $removedIpAddress->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

            if ($this->RemovedIpAddresses->save($removedIpAddress)) {
                $this->Flash->success(__('The removed IP address has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $removedIpAddress->id]);
            }
            $this->Flash->error(__('The removed IP address could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIpAddresses->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->RemovedIpAddresses->Contracts->find(
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

        $this->set(compact('removedIpAddress', 'customers', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Removed IP Address id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $removedIpAddress = $this->RemovedIpAddresses->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $removedIpAddress = $this->RemovedIpAddresses->patchEntity(
                $removedIpAddress,
                $this->getRequest()->getData(),
            );

            if ($this->RemovedIpAddresses->save($removedIpAddress)) {
                $this->Flash->success(__('The removed IP address has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $removedIpAddress->id]);
            }
            $this->Flash->error(__('The removed IP address could not be saved. Please, try again.'));
        }
        $customers = $this->RemovedIpAddresses->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->RemovedIpAddresses->Contracts->find(
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

        $this->set(compact('removedIpAddress', 'customers', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Removed IP Address id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $removedIpAddress = $this->RemovedIpAddresses->get($id);
        if ($this->RemovedIpAddresses->delete($removedIpAddress)) {
            $this->Flash->success(__('The removed IP address has been deleted.'));
        } else {
            $this->flashValidationErrors($removedIpAddress->getErrors());
            $this->Flash->error(__('The removed IP address could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
