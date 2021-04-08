<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * EquipmentTypes Controller
 *
 * @property \App\Model\Table\EquipmentTypesTable $EquipmentTypes
 * @method \App\Model\Entity\EquipmentType[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $equipmentTypes = $this->paginate($this->EquipmentTypes);

        $this->set(compact('equipmentTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Equipment Type id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $equipmentType = $this->EquipmentTypes->get($id, [
            'contain' => ['BorrowedEquipments', 'SoldEquipments'],
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
        if ($this->request->is('post')) {
            $equipmentType = $this->EquipmentTypes->patchEntity($equipmentType, $this->request->getData());
            if ($this->EquipmentTypes->save($equipmentType)) {
                $this->Flash->success(__('The equipment type has been saved.'));

                return $this->redirect(['action' => 'index']);
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
    public function edit($id = null)
    {
        $equipmentType = $this->EquipmentTypes->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $equipmentType = $this->EquipmentTypes->patchEntity($equipmentType, $this->request->getData());
            if ($this->EquipmentTypes->save($equipmentType)) {
                $this->Flash->success(__('The equipment type has been saved.'));

                return $this->redirect(['action' => 'index']);
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
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $equipmentType = $this->EquipmentTypes->get($id);
        if ($this->EquipmentTypes->delete($equipmentType)) {
            $this->Flash->success(__('The equipment type has been deleted.'));
        } else {
            $this->Flash->error(__('The equipment type could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
