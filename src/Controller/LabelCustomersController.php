<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * LabelCustomers Controller
 *
 * @property \App\Model\Table\LabelCustomersTable $LabelCustomers
 * @method \App\Model\Entity\LabelCustomer[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class LabelCustomersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Labels', 'Customers'],
        ];
        $labelCustomers = $this->paginate($this->LabelCustomers);

        $this->set(compact('labelCustomers'));
    }

    /**
     * View method
     *
     * @param string|null $id Label Customer id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $labelCustomer = $this->LabelCustomers->get($id, [
            'contain' => ['Labels', 'Customers'],
        ]);

        $this->set(compact('labelCustomer'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $labelCustomer = $this->LabelCustomers->newEmptyEntity();
        if ($this->request->is('post')) {
            $labelCustomer = $this->LabelCustomers->patchEntity($labelCustomer, $this->request->getData());
            if ($this->LabelCustomers->save($labelCustomer)) {
                $this->Flash->success(__('The label customer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The label customer could not be saved. Please, try again.'));
        }
        $labels = $this->LabelCustomers->Labels->find('list', ['order' => 'name']);
        $customers = $this->LabelCustomers->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $this->set(compact('labelCustomer', 'labels', 'customers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Label Customer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $labelCustomer = $this->LabelCustomers->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $labelCustomer = $this->LabelCustomers->patchEntity($labelCustomer, $this->request->getData());
            if ($this->LabelCustomers->save($labelCustomer)) {
                $this->Flash->success(__('The label customer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The label customer could not be saved. Please, try again.'));
        }
        $labels = $this->LabelCustomers->Labels->find('list', ['order' => 'name']);
        $customers = $this->LabelCustomers->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $this->set(compact('labelCustomer', 'labels', 'customers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Label Customer id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $labelCustomer = $this->LabelCustomers->get($id);
        if ($this->LabelCustomers->delete($labelCustomer)) {
            $this->Flash->success(__('The label customer has been deleted.'));
        } else {
            $this->Flash->error(__('The label customer could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
