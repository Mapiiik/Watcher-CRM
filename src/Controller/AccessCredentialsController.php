<?php
declare(strict_types=1);

namespace App\Controller;

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
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions += ['AccessCredentials.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
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
                    'AccessCredentials.name ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
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
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
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
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $accessCredential = $this->AccessCredentials->newEmptyEntity();

        if (isset($this->customer_id)) {
            $accessCredential->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $accessCredential->contract_id = $this->contract_id;
        }

        if ($this->request->is('post')) {
            $accessCredential = $this->AccessCredentials->patchEntity($accessCredential, $this->request->getData());
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
        if (isset($this->customer_id)) {
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
    }

    /**
     * Edit method
     *
     * @param string|null $id Access Credential id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
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
        if (isset($this->customer_id)) {
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
    }

    /**
     * Delete method
     *
     * @param string|null $id Access Credential id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
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
