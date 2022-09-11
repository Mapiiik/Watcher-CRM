<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * CustomerLabels Controller
 *
 * @property \App\Model\Table\CustomerLabelsTable $CustomerLabels
 * @method \App\Model\Entity\CustomerLabel[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions = ['CustomerLabels.customer_id' => $customer_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Labels.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => ['Labels', 'Customers'],
            'order' => ['id' => 'DESC'],
            'conditions' => $conditions,
        ];
        $customerLabels = $this->paginate($this->CustomerLabels);

        $this->set(compact('customerLabels'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer Label id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $customerLabel = $this->CustomerLabels->get($id, [
            'contain' => [
                'Labels',
                'Customers',
                'Creators',
                'Modifiers',
            ],
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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $customerLabel = $this->CustomerLabels->newEmptyEntity();

        if (isset($customer_id)) {
            $customerLabel->customer_id = $customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $customerLabel = $this->CustomerLabels->patchEntity($customerLabel, $this->getRequest()->getData());
            if ($this->CustomerLabels->save($customerLabel)) {
                $this->Flash->success(__('The customer label has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The customer label could not be saved. Please, try again.'));
        }
        $labels = $this->CustomerLabels->Labels->find('list', ['order' => 'name']);
        $customers = $this->CustomerLabels->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $this->set(compact('customerLabel', 'labels', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer Label id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $customerLabel = $this->CustomerLabels->get($id, [
            'contain' => [],
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $customerLabel = $this->CustomerLabels->patchEntity($customerLabel, $this->getRequest()->getData());
            if ($this->CustomerLabels->save($customerLabel)) {
                $this->Flash->success(__('The customer label has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The customer label could not be saved. Please, try again.'));
        }
        $labels = $this->CustomerLabels->Labels->find('list', ['order' => 'name']);
        $customers = $this->CustomerLabels->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $this->set(compact('customerLabel', 'labels', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer Label id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $customerLabel = $this->CustomerLabels->get($id);
        if ($this->CustomerLabels->delete($customerLabel)) {
            $this->Flash->success(__('The customer label has been deleted.'));
        } else {
            $this->Flash->error(__('The customer label could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
