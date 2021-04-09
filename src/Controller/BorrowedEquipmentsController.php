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
        $this->paginate = [
            'contain' => ['Customers', 'Contracts', 'EquipmentTypes'],
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
        $borrowedEquipment = $this->BorrowedEquipments->newEmptyEntity();
        if ($this->request->is('post')) {
            $borrowedEquipment = $this->BorrowedEquipments->patchEntity($borrowedEquipment, $this->request->getData());
            if ($this->BorrowedEquipments->save($borrowedEquipment)) {
                $this->Flash->success(__('The borrowed equipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The borrowed equipment could not be saved. Please, try again.'));
        }
        $customers = $this->BorrowedEquipments->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->BorrowedEquipments->Contracts->find('list', ['order' => 'name']);
        $equipmentTypes = $this->BorrowedEquipments->EquipmentTypes->find('list', ['order' => 'name']);
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
        $borrowedEquipment = $this->BorrowedEquipments->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $borrowedEquipment = $this->BorrowedEquipments->patchEntity($borrowedEquipment, $this->request->getData());
            if ($this->BorrowedEquipments->save($borrowedEquipment)) {
                $this->Flash->success(__('The borrowed equipment has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The borrowed equipment could not be saved. Please, try again.'));
        }
        $customers = $this->BorrowedEquipments->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->BorrowedEquipments->Contracts->find('list', ['order' => 'name']);
        $equipmentTypes = $this->BorrowedEquipments->EquipmentTypes->find('list', ['order' => 'name']);
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
        $this->request->allowMethod(['post', 'delete']);
        $borrowedEquipment = $this->BorrowedEquipments->get($id);
        if ($this->BorrowedEquipments->delete($borrowedEquipment)) {
            $this->Flash->success(__('The borrowed equipment has been deleted.'));
        } else {
            $this->Flash->error(__('The borrowed equipment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
