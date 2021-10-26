<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * BorrowedEquipments Controller
 *
 * @property \App\Model\Table\BorrowedEquipmentsTable $BorrowedEquipments
 * @method \App\Model\Entity\BorrowedEquipment[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BorrowedEquipmentsController extends AppController
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
            $conditions += ['BorrowedEquipments.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['BorrowedEquipments.contract_id' => $contract_id];
        }
        
        $this->paginate = [
            'contain' => ['Customers', 'Contracts', 'EquipmentTypes'],
            'conditions' => $conditions,
        ];
        $borrowedEquipments = $this->paginate($this->BorrowedEquipments);

        $this->set(compact('borrowedEquipments'));
    }

    /**
     * View method
     *
     * @param string|null $id Borrowed Equipment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $borrowedEquipment = $this->BorrowedEquipments->get($id, [
            'contain' => ['Customers', 'Contracts', 'EquipmentTypes'],
        ]);

        $this->set(compact('borrowedEquipment'));
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

        $borrowedEquipment = $this->BorrowedEquipments->newEmptyEntity();
        
        if (isset($customer_id)) $borrowedEquipment = $this->BorrowedEquipments->patchEntity($borrowedEquipment, ['customer_id' => $customer_id]);
        if (isset($contract_id)) $borrowedEquipment = $this->BorrowedEquipments->patchEntity($borrowedEquipment, ['contract_id' => $contract_id]);
        
        if ($this->request->is('post')) {
            $borrowedEquipment = $this->BorrowedEquipments->patchEntity($borrowedEquipment, $this->request->getData());
            if ($this->BorrowedEquipments->save($borrowedEquipment)) {
                $this->Flash->success(__('The borrowed equipment has been saved.'));

                if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The borrowed equipment could not be saved. Please, try again.'));
        }
        $customers = $this->BorrowedEquipments->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->BorrowedEquipments->Contracts->find('list', ['order' => 'Contracts.number', 'contain' => ['ServiceTypes', 'InstallationAddresses']]);
        $equipmentTypes = $this->BorrowedEquipments->EquipmentTypes->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $this->set(compact('borrowedEquipment', 'customers', 'contracts', 'equipmentTypes'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Borrowed Equipment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $borrowedEquipment = $this->BorrowedEquipments->get($id, [
            'contain' => [],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $borrowedEquipment = $this->BorrowedEquipments->patchEntity($borrowedEquipment, $this->request->getData());
            if ($this->BorrowedEquipments->save($borrowedEquipment)) {
                $this->Flash->success(__('The borrowed equipment has been saved.'));

                if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The borrowed equipment could not be saved. Please, try again.'));
        }
        $customers = $this->BorrowedEquipments->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->BorrowedEquipments->Contracts->find('list', ['order' => 'Contracts.number', 'contain' => ['ServiceTypes', 'InstallationAddresses']]);
        $equipmentTypes = $this->BorrowedEquipments->EquipmentTypes->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $this->set(compact('borrowedEquipment', 'customers', 'contracts', 'equipmentTypes'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Borrowed Equipment id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $contract_id = $this->request->getParam('contract_id');

        $this->request->allowMethod(['post', 'delete']);
        $borrowedEquipment = $this->BorrowedEquipments->get($id);
        if ($this->BorrowedEquipments->delete($borrowedEquipment)) {
            $this->Flash->success(__('The borrowed equipment has been deleted.'));
        } else {
            $this->Flash->error(__('The borrowed equipment could not be deleted. Please, try again.'));
        }

        if (isset($contract_id)) return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
                
        return $this->redirect(['action' => 'index']);
    }
}
