<?php
declare(strict_types=1);

namespace RADIUS\Controller;

use RADIUS\Controller\AppController;

/**
 * Radgroupcheck Controller
 *
 * @property \RADIUS\Model\Table\RadgroupcheckTable $Radgroupcheck
 * @method \RADIUS\Model\Entity\Radgroupcheck[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RadgroupcheckController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $radgroupcheck = $this->paginate($this->Radgroupcheck);

        $this->set(compact('radgroupcheck'));
    }

    /**
     * View method
     *
     * @param string|null $id Radgroupcheck id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $radgroupcheck = $this->Radgroupcheck->get($id, [
            'contain' => ['Radusergroup'],
        ]);

        $this->set(compact('radgroupcheck'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radgroupcheck = $this->Radgroupcheck->newEmptyEntity();
        if ($this->request->is('post')) {
            $radgroupcheck = $this->Radgroupcheck->patchEntity($radgroupcheck, $this->request->getData());
            if ($this->Radgroupcheck->save($radgroupcheck)) {
                $this->Flash->success(__('The radgroupcheck has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radgroupcheck could not be saved. Please, try again.'));
        }
        $this->set(compact('radgroupcheck'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radgroupcheck id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $radgroupcheck = $this->Radgroupcheck->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $radgroupcheck = $this->Radgroupcheck->patchEntity($radgroupcheck, $this->request->getData());
            if ($this->Radgroupcheck->save($radgroupcheck)) {
                $this->Flash->success(__('The radgroupcheck has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radgroupcheck could not be saved. Please, try again.'));
        }
        $this->set(compact('radgroupcheck'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radgroupcheck id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $radgroupcheck = $this->Radgroupcheck->get($id);
        if ($this->Radgroupcheck->delete($radgroupcheck)) {
            $this->Flash->success(__('The radgroupcheck has been deleted.'));
        } else {
            $this->Flash->error(__('The radgroupcheck could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
