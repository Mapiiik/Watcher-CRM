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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['SoldEquipments.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['SoldEquipments.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'SoldEquipments.serial_number ILIKE' => '%' . trim($search) . '%',
                    'EquipmentTypes.name ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts', 'EquipmentTypes'],
            'order' => ['id' => 'DESC'],
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
        $soldEquipment = $this->SoldEquipments->get($id, contain: [
            'Customers',
            'Contracts',
            'EquipmentTypes',
            'Creators',
            'Modifiers',
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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $soldEquipment = $this->SoldEquipments->newEmptyEntity();

        if (isset($customer_id)) {
            $soldEquipment->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $soldEquipment->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $soldEquipment = $this->SoldEquipments
                ->patchEntity($soldEquipment, $this->getRequest()->getData());

            if ($this->SoldEquipments->save($soldEquipment)) {
                $this->Flash->success(__('The sold equipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sold equipment could not be saved. Please, try again.'));
        }
        $customers = $this->SoldEquipments->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->SoldEquipments->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);
        $equipmentTypes = $this->SoldEquipments->EquipmentTypes->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $soldEquipment = $this->SoldEquipments->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $soldEquipment = $this->SoldEquipments
                ->patchEntity($soldEquipment, $this->getRequest()->getData());

            if ($this->SoldEquipments->save($soldEquipment)) {
                $this->Flash->success(__('The sold equipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sold equipment could not be saved. Please, try again.'));
        }
        $customers = $this->SoldEquipments->Customers->find(
            'list',
            ['order' => ['company', 'last_name', 'first_name']]
        );
        $contracts = $this->SoldEquipments->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);
        $equipmentTypes = $this->SoldEquipments->EquipmentTypes->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $contract_id = $this->getRequest()->getParam('contract_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $soldEquipment = $this->SoldEquipments->get($id);
        if ($this->SoldEquipments->delete($soldEquipment)) {
            $this->Flash->success(__('The sold equipment has been deleted.'));
        } else {
            $this->Flash->error(__('The sold equipment could not be deleted. Please, try again.'));
        }

        if (isset($contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }
}
