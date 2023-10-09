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
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions += ['SoldEquipments.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['SoldEquipments.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
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
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $soldEquipments = $this->paginate($this->SoldEquipments->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
                'EquipmentTypes',
            ],
            conditions: $conditions
        ));

        $this->set(compact('soldEquipments'));
    }

    /**
     * View method
     *
     * @param string|null $id Sold Equipment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
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
        $soldEquipment = $this->SoldEquipments->newEmptyEntity();

        if (isset($this->customer_id)) {
            $soldEquipment->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $soldEquipment->contract_id = $this->contract_id;
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
        $customers = $this->SoldEquipments->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->SoldEquipments->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $equipmentTypes = $this->SoldEquipments->EquipmentTypes->find('list', order: [
        'name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
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
    public function edit(?string $id = null)
    {
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
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->SoldEquipments->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $equipmentTypes = $this->SoldEquipments->EquipmentTypes->find('list', order: [
        'name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
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
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $soldEquipment = $this->SoldEquipments->get($id);
        if ($this->SoldEquipments->delete($soldEquipment)) {
            $this->Flash->success(__('The sold equipment has been deleted.'));
        } else {
            $this->Flash->error(__('The sold equipment could not be deleted. Please, try again.'));
        }

        if (isset($this->contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $this->contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }
}
