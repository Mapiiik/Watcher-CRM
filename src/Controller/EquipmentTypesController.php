<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * EquipmentTypes Controller
 *
 * @property \App\Model\Table\EquipmentTypesTable $EquipmentTypes
 */
class EquipmentTypesController extends AppController
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

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'EquipmentTypes.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];

        $equipmentTypes = $this->paginate($this->EquipmentTypes->find(
            'all',
            contain: [],
            conditions: $conditions
        ));

        $this->set(compact('equipmentTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Equipment Type id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $equipmentType = $this->EquipmentTypes->get($id, contain: [
            'BorrowedEquipments' => ['Customers', 'Contracts'],
            'SoldEquipments' => ['Customers', 'Contracts'],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('equipmentType'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $equipmentType = $this->EquipmentTypes->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $equipmentType = $this->EquipmentTypes->patchEntity($equipmentType, $this->getRequest()->getData());
            if ($this->EquipmentTypes->save($equipmentType)) {
                $this->Flash->success(__('The equipment type has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $equipmentType->id]);
            }
            $this->Flash->error(__('The equipment type could not be saved. Please, try again.'));
        }
        $this->set(compact('equipmentType'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Equipment Type id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $equipmentType = $this->EquipmentTypes->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $equipmentType = $this->EquipmentTypes->patchEntity($equipmentType, $this->getRequest()->getData());
            if ($this->EquipmentTypes->save($equipmentType)) {
                $this->Flash->success(__('The equipment type has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $equipmentType->id]);
            }
            $this->Flash->error(__('The equipment type could not be saved. Please, try again.'));
        }
        $this->set(compact('equipmentType'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Equipment Type id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $equipmentType = $this->EquipmentTypes->get($id);
        if ($this->EquipmentTypes->delete($equipmentType)) {
            $this->Flash->success(__('The equipment type has been deleted.'));
        } else {
            $this->flashValidationErrors($equipmentType->getErrors());
            $this->Flash->error(__('The equipment type could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
