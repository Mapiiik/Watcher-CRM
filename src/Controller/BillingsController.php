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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['Billings.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Billings.contract_id' => $contract_id];
        }
        
        $this->paginate = [
            'contain' => ['Customers', 'Services', 'Contracts'],
            'conditions' => $conditions,
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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $billing = $this->Billings->newEmptyEntity();

        if (isset($customer_id)) $billing = $this->Billings->patchEntity($billing, ['customer_id' => $customer_id]);
        if (isset($contract_id)) $billing = $this->Billings->patchEntity($billing, ['contract_id' => $contract_id]);
        
        if ($this->request->is('post')) {
            $billing = $this->Billings->patchEntity($billing, $this->request->getData());
            
            if (
                isset($billing->service_id) &&
                isset($this->Billings->Services->get($billing->service_id)->service_type_id) && 
                $this->Billings->Contracts->get($billing->contract_id)->service_type_id <> $this->Billings->Services->get($billing->service_id)->service_type_id
            ) {
                $this->Flash->error(__('The service type does not match the selected contract.'));
            } else {
                if ($this->Billings->save($billing)) {
                    $this->Flash->success(__('The billing has been saved.'));

                    if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                    
                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The billing could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Billings->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Billings->Contracts->find('list', ['order' => 'number']);
        $services = $this->Billings->Services->find('list', ['order' => 'name']);
        
        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $contracts->where(['customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['id' => $contract_id]);
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }
        
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
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);
        
        $billing = $this->Billings->get($id, [
            'contain' => [],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $billing = $this->Billings->patchEntity($billing, $this->request->getData());
            if (
                isset($billing->service_id) &&
                isset($this->Billings->Services->get($billing->service_id)->service_type_id) && 
                $this->Billings->Contracts->get($billing->contract_id)->service_type_id <> $this->Billings->Services->get($billing->service_id)->service_type_id
            ) {
                $this->Flash->error(__('The service type does not match the selected contract.'));
            } else {
                if ($this->Billings->save($billing)) {
                    $this->Flash->success(__('The billing has been saved.'));

                    if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                    
                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The billing could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Billings->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Billings->Contracts->find('list', ['order' => 'number']);
        $services = $this->Billings->Services->find('list', ['order' => 'name']);
        
        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $contracts->where(['customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['id' => $contract_id]);
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }
        
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
        $customer_id = $this->request->getParam('customer_id');
        $contract_id = $this->request->getParam('contract_id');

        $this->request->allowMethod(['post', 'delete']);
        $billing = $this->Billings->get($id);
        if ($this->Billings->delete($billing)) {
            $this->Flash->success(__('The billing has been deleted.'));
        } else {
            $this->Flash->error(__('The billing could not be deleted. Please, try again.'));
        }

        if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
        return $this->redirect(['action' => 'index']);
    }
}
