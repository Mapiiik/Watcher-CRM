<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * SoldEquipments Controller
 *
 * @property \App\Model\Table\SoldEquipmentsTable $SoldEquipments
 * @method \App\Model\Entity\SoldEquipment[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class SoldEquipmentsController extends AppController
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
            $conditions += ['SoldEquipments.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['SoldEquipments.contract_id' => $contract_id];
        }
        
        $this->paginate = [
            'contain' => ['Customers', 'Contracts', 'EquipmentTypes'],
            'conditions' => $conditions,
        ];
        $soldEquipments = $this->paginate($this->SoldEquipments);

        $this->set(compact('soldEquipments'));
    }

    /**
     * View method
     *
     * @param string|null $id Sold Equipment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $soldEquipment = $this->SoldEquipments->get($id, [
            'contain' => ['Customers', 'Contracts', 'EquipmentTypes'],
        ]);

        $this->set(compact('soldEquipment'));
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

        $soldEquipment = $this->SoldEquipments->newEmptyEntity();

        $conditions = [];
        if (isset($customer_id)) {
            $soldEquipment = $this->SoldEquipments->patchEntity($soldEquipment, ['customer_id' => $customer_id]);
            $conditions += ['customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $soldEquipment = $this->SoldEquipments->patchEntity($soldEquipment, ['contract_id' => $contract_id]);
        }
        
        if ($this->request->is('post')) {
            $soldEquipment = $this->SoldEquipments->patchEntity($soldEquipment, $this->request->getData());
            if ($this->SoldEquipments->save($soldEquipment)) {
                $this->Flash->success(__('The sold equipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sold equipment could not be saved. Please, try again.'));
        }
        $customers = $this->SoldEquipments->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->SoldEquipments->Contracts->find('list', ['order' => 'number', 'conditions' => $conditions]);
        $equipmentTypes = $this->SoldEquipments->EquipmentTypes->find('list', ['order' => 'name']);
        $this->set(compact('soldEquipment', 'customers', 'contracts', 'equipmentTypes'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Sold Equipment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $soldEquipment = $this->SoldEquipments->get($id, [
            'contain' => [],
        ]);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['customer_id' => $customer_id];
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $soldEquipment = $this->SoldEquipments->patchEntity($soldEquipment, $this->request->getData());
            if ($this->SoldEquipments->save($soldEquipment)) {
                $this->Flash->success(__('The sold equipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sold equipment could not be saved. Please, try again.'));
        }
        $customers = $this->SoldEquipments->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->SoldEquipments->Contracts->find('list', ['order' => 'number', 'conditions' => $conditions]);
        $equipmentTypes = $this->SoldEquipments->EquipmentTypes->find('list', ['order' => 'name']);
        $this->set(compact('soldEquipment', 'customers', 'contracts', 'equipmentTypes'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Sold Equipment id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);
        
        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $this->request->allowMethod(['post', 'delete']);
        $soldEquipment = $this->SoldEquipments->get($id);
        if ($this->SoldEquipments->delete($soldEquipment)) {
            $this->Flash->success(__('The sold equipment has been deleted.'));
        } else {
            $this->Flash->error(__('The sold equipment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
