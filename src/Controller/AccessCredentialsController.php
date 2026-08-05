<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * AccessCredentials Controller
 *
 * @property \App\Model\Table\AccessCredentialsTable $AccessCredentials
 */
class AccessCredentialsController extends AppController
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
            $conditions += ['AccessCredentials.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += [
                'OR' => [
                    'AccessCredentials.contract_id' => $this->contract_id,
                    'AccessCredentials.contract_id IS NULL',
                ],
            ];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'AccessCredentials.name ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $query = $this->AccessCredentials->find()
            ->contain([
                'Creators',
                'Modifiers',
                'Customers',
                'Contracts',
            ])
            ->where($conditions);

        $accessCredentials = $this->paginate($query);

        $this->set(compact('accessCredentials'));
    }

    /**
     * View method
     *
     * @param string|null $id Access Credential id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $accessCredential = $this->AccessCredentials->get($id, contain: [
            'Creators',
            'Modifiers',
            'Customers',
            'Contracts',
        ]);
        $this->set(compact('accessCredential'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $accessCredential = $this->AccessCredentials->newEmptyEntity();

        if ($this->customer_id !== null) {
            $accessCredential->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $accessCredential->contract_id = $this->contract_id;
        }

        if ($this->request->is('post')) {
            $accessCredential = $this->AccessCredentials->patchEntity(
                $accessCredential,
                $this->dataWithAdditionalParameters($this->AccessCredentials, $this->request->getData()),
            );
            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->AccessCredentials->save($accessCredential)) {
                    $this->Flash->success(__('The access credential has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $accessCredential->id]);
                }
                $this->Flash->error(__('The access credential could not be saved. Please, try again.'));
            }
        }

        $customersQuery = $this->AccessCredentials->Customers
        ->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        if ($this->customer_id !== null) {
            $customersQuery->where(['Customers.id' => $this->customer_id]);
        }
        $customers = $customersQuery->all();

        if (isset($accessCredential->customer_id)) {
            $contracts = $this->AccessCredentials->Contracts
                ->find(
                    'list',
                    contain: [
                        'InstallationAddresses',
                        'ServiceTypes',
                    ],
                    conditions: [
                        'Contracts.customer_id' => $accessCredential->customer_id,
                    ],
                    order: [
                        'Contracts.number',
                    ],
                )
                ->all();
        } else {
            $contracts = [];
        }

        $this->set(compact('accessCredential', 'customers', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Access Credential id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $accessCredential = $this->AccessCredentials->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $accessCredential = $this->AccessCredentials->patchEntity($accessCredential, $this->request->getData());
            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->AccessCredentials->save($accessCredential)) {
                    $this->Flash->success(__('The access credential has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $accessCredential->id]);
                }
                $this->Flash->error(__('The access credential could not be saved. Please, try again.'));
            }
        }

        $customersQuery = $this->AccessCredentials->Customers
        ->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        if ($this->customer_id !== null) {
            $customersQuery->where(['Customers.id' => $this->customer_id]);
        }
        $customers = $customersQuery->all();

        if (isset($accessCredential->customer_id)) {
            $contracts = $this->AccessCredentials->Contracts
                ->find(
                    'list',
                    contain: [
                        'InstallationAddresses',
                        'ServiceTypes',
                    ],
                    conditions: [
                        'Contracts.customer_id' => $accessCredential->customer_id,
                    ],
                    order: [
                        'Contracts.number',
                    ],
                )
                ->all();
        } else {
            $contracts = [];
        }

        $this->set(compact('accessCredential', 'customers', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Access Credential id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $accessCredential = $this->AccessCredentials->get($id);
        if ($this->AccessCredentials->delete($accessCredential)) {
            $this->Flash->success(__('The access credential has been deleted.'));
        } else {
            $this->Flash->error(__('The access credential could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
