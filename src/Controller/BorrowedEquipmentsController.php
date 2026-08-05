<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * BorrowedEquipments Controller
 *
 * @property \App\Model\Table\BorrowedEquipmentsTable $BorrowedEquipments
 */
class BorrowedEquipmentsController extends AppController
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
        if ($this->customer_id !== null) {
            $conditions += ['BorrowedEquipments.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['BorrowedEquipments.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'BorrowedEquipments.serial_number ILIKE' => '%' . trim((string)$search) . '%',
                    'EquipmentTypes.name ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $borrowedEquipments = $this->paginate($this->BorrowedEquipments->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
                'EquipmentTypes',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('borrowedEquipments'));
    }

    /**
     * View method
     *
     * @param string|null $id Borrowed Equipment id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $borrowedEquipment = $this->BorrowedEquipments->get($id, contain: [
            'Customers',
            'Contracts',
            'EquipmentTypes',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('borrowedEquipment'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $borrowedEquipment = $this->BorrowedEquipments->newEmptyEntity();

        if ($this->customer_id !== null) {
            $borrowedEquipment->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $borrowedEquipment->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $borrowedEquipment = $this->BorrowedEquipments->patchEntity(
                $borrowedEquipment,
                $this->dataWithAdditionalParameters($this->BorrowedEquipments, $this->getRequest()->getData()),
            );

            if ($this->BorrowedEquipments->save($borrowedEquipment)) {
                $this->Flash->success(__('The borrowed equipment has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $borrowedEquipment->id]);
            }
            $this->Flash->error(__('The borrowed equipment could not be saved. Please, try again.'));
        }
        $customers = $this->BorrowedEquipments->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->BorrowedEquipments->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $equipmentTypes = $this->BorrowedEquipments->EquipmentTypes->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('borrowedEquipment', 'customers', 'contracts', 'equipmentTypes'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Borrowed Equipment id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $borrowedEquipment = $this->BorrowedEquipments->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $borrowedEquipment = $this->BorrowedEquipments
                ->patchEntity($borrowedEquipment, $this->getRequest()->getData());

            if ($this->BorrowedEquipments->save($borrowedEquipment)) {
                $this->Flash->success(__('The borrowed equipment has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $borrowedEquipment->id]);
            }
            $this->Flash->error(__('The borrowed equipment could not be saved. Please, try again.'));
        }
        $customers = $this->BorrowedEquipments->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->BorrowedEquipments->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $equipmentTypes = $this->BorrowedEquipments->EquipmentTypes->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('borrowedEquipment', 'customers', 'contracts', 'equipmentTypes'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Borrowed Equipment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $borrowedEquipment = $this->BorrowedEquipments->get($id);
        if ($this->BorrowedEquipments->delete($borrowedEquipment)) {
            $this->Flash->success(__('The borrowed equipment has been deleted.'));
        } else {
            $this->flashValidationErrors($borrowedEquipment->getErrors());
            $this->Flash->error(__('The borrowed equipment could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
