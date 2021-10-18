<?php
declare(strict_types=1);

namespace RADIUS\Controller;

use RADIUS\Controller\AppController;

/**
 * Radcheck Controller
 *
 * @property \RADIUS\Model\Table\RadcheckTable $Radcheck
 * @method \RADIUS\Model\Entity\Radcheck[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RadcheckController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Accounts'],
        ];
        $radcheck = $this->paginate($this->Radcheck);

        $this->set(compact('radcheck'));
    }

    /**
     * View method
     *
     * @param string|null $id Radcheck id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $radcheck = $this->Radcheck->get($id, [
            'contain' => ['Accounts'],
        ]);

        $this->set(compact('radcheck'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radcheck = $this->Radcheck->newEmptyEntity();
        if ($this->request->is('post')) {
            $radcheck = $this->Radcheck->patchEntity($radcheck, $this->request->getData());
            if ($this->Radcheck->save($radcheck)) {
                $this->Flash->success(__('The radcheck has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radcheck could not be saved. Please, try again.'));
        }
        $accounts = $this->Radcheck->Accounts->find('list', ['keyField' => 'username', 'order' => 'username']);
        $this->set(compact('radcheck', 'accounts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radcheck id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $radcheck = $this->Radcheck->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $radcheck = $this->Radcheck->patchEntity($radcheck, $this->request->getData());
            if ($this->Radcheck->save($radcheck)) {
                $this->Flash->success(__('The radcheck has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radcheck could not be saved. Please, try again.'));
        }
        $accounts = $this->Radcheck->Accounts->find('list', ['keyField' => 'username', 'order' => 'username']);
        $this->set(compact('radcheck', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radcheck id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $radcheck = $this->Radcheck->get($id);
        if ($this->Radcheck->delete($radcheck)) {
            $this->Flash->success(__('The radcheck has been deleted.'));
        } else {
            $this->Flash->error(__('The radcheck could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
