<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

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
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'EquipmentTypes.name ILIKE' => '%' . trim((string)$search) . '%',
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
            conditions: $conditions,
        ));

        $this->set(compact('equipmentTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Equipment Type id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
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
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
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

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Equipment Type id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Equipment Type id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
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
