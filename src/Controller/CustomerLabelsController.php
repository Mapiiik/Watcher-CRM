<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * CustomerLabels Controller
 *
 * @property \App\Model\Table\CustomerLabelsTable $CustomerLabels
 */
class CustomerLabelsController extends AppController
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
            $conditions = ['CustomerLabels.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Labels.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $customerLabels = $this->paginate($this->CustomerLabels->find(
            'all',
            contain: [
                'Customers',
                'Contracts',
                'Labels',
            ],
            conditions: $conditions
        ));

        $this->set(compact('customerLabels'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer Label id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $customerLabel = $this->CustomerLabels->get($id, contain: [
            'Labels',
            'Customers',
            'Contracts',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('customerLabel'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customerLabel = $this->CustomerLabels->newEmptyEntity();

        if (isset($this->customer_id)) {
            $customerLabel->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $customerLabel = $this->CustomerLabels->patchEntity($customerLabel, $this->getRequest()->getData());
            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->CustomerLabels->save($customerLabel)) {
                    $this->Flash->success(__('The customer label has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $customerLabel->id]);
                }
                $this->Flash->error(__('The customer label could not be saved. Please, try again.'));
            }
        }

        $labels = $this->CustomerLabels->Labels->find('list', order: [
            'name',
        ]);
        $customers = $this->CustomerLabels->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = isset($customerLabel->customer_id) ?
            $this->CustomerLabels->Contracts->find(
                'list',
                contain: [
                    'InstallationAddresses',
                    'ServiceTypes',
                ],
                conditions: [
                    'Contracts.customer_id' => $customerLabel->customer_id,
                ],
                order: [
                    'Contracts.number',
                ],
            ) :
            [];

        $this->set(compact('customerLabel', 'labels', 'customers', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer Label id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $customerLabel = $this->CustomerLabels->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $customerLabel = $this->CustomerLabels->patchEntity($customerLabel, $this->getRequest()->getData());
            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->CustomerLabels->save($customerLabel)) {
                    $this->Flash->success(__('The customer label has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $customerLabel->id]);
                }
                $this->Flash->error(__('The customer label could not be saved. Please, try again.'));
            }
        }

        $labels = $this->CustomerLabels->Labels->find('list', order: [
            'name',
        ]);
        $customers = $this->CustomerLabels->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = isset($customerLabel->customer_id) ?
            $this->CustomerLabels->Contracts->find(
                'list',
                contain: [
                    'InstallationAddresses',
                    'ServiceTypes',
                ],
                conditions: [
                    'Contracts.customer_id' => $customerLabel->customer_id,
                ],
                order: [
                    'Contracts.number',
                ],
            ) :
            [];

        $this->set(compact('customerLabel', 'labels', 'customers', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer Label id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $customerLabel = $this->CustomerLabels->get($id);
        if ($this->CustomerLabels->delete($customerLabel)) {
            $this->Flash->success(__('The customer label has been deleted.'));
        } else {
            $this->flashValidationErrors($customerLabel->getErrors());
            $this->Flash->error(__('The customer label could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
