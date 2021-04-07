<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Billings Controller
 *
 * @property \App\Model\Table\BillingsTable $Billings
 * @method \App\Model\Entity\Billing[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BillingsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Customers', 'Services', 'Contracts'],
        ];
        $billings = $this->paginate($this->Billings);

        $this->set(compact('billings'));
    }

    /**
     * View method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $billing = $this->Billings->get($id, [
            'contain' => ['Customers', 'Services', 'Contracts'],
        ]);

        $this->set(compact('billing'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $billing = $this->Billings->newEmptyEntity();
        if ($this->request->is('post')) {
            $billing = $this->Billings->patchEntity($billing, $this->request->getData());
            if ($this->Billings->save($billing)) {
                $this->Flash->success(__('The billing has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The billing could not be saved. Please, try again.'));
        }
        $customers = $this->Billings->Customers->find('list', ['limit' => 200]);
        $services = $this->Billings->Services->find('list', ['limit' => 200]);
        $contracts = $this->Billings->Contracts->find('list', ['limit' => 200]);
        $this->set(compact('billing', 'customers', 'services', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $billing = $this->Billings->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $billing = $this->Billings->patchEntity($billing, $this->request->getData());
            if ($this->Billings->save($billing)) {
                $this->Flash->success(__('The billing has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The billing could not be saved. Please, try again.'));
        }
        $customers = $this->Billings->Customers->find('list', ['limit' => 200]);
        $services = $this->Billings->Services->find('list', ['limit' => 200]);
        $contracts = $this->Billings->Contracts->find('list', ['limit' => 200]);
        $this->set(compact('billing', 'customers', 'services', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $billing = $this->Billings->get($id);
        if ($this->Billings->delete($billing)) {
            $this->Flash->success(__('The billing has been deleted.'));
        } else {
            $this->Flash->error(__('The billing could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
