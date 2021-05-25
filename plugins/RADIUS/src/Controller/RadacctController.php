<?php
declare(strict_types=1);

namespace RADIUS\Controller;

use RADIUS\Controller\AppController;

/**
 * Radacct Controller
 *
 * @property \RADIUS\Model\Table\RadacctTable $Radacct
 * @method \RADIUS\Model\Entity\Radacct[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RadacctController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['Radcheck'],
        ];
        $radacct = $this->paginate($this->Radacct);

        $this->set(compact('radacct'));
    }

    /**
     * View method
     *
     * @param string|null $id Radacct id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $radacct = $this->Radacct->get($id, [
            'contain' => ['Radcheck'],
        ]);

        $this->set(compact('radacct'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radacct = $this->Radacct->newEmptyEntity();
        if ($this->request->is('post')) {
            $radacct = $this->Radacct->patchEntity($radacct, $this->request->getData());
            if ($this->Radacct->save($radacct)) {
                $this->Flash->success(__('The radacct has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radacct could not be saved. Please, try again.'));
        }
        $radcheck = $this->Radacct->Radcheck->find('list', ['limit' => 200]);
        $this->set(compact('radacct', 'radcheck'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radacct id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $radacct = $this->Radacct->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $radacct = $this->Radacct->patchEntity($radacct, $this->request->getData());
            if ($this->Radacct->save($radacct)) {
                $this->Flash->success(__('The radacct has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radacct could not be saved. Please, try again.'));
        }
        $radcheck = $this->Radacct->Radcheck->find('list', ['limit' => 200]);
        $this->set(compact('radacct', 'radcheck'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radacct id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $radacct = $this->Radacct->get($id);
        if ($this->Radacct->delete($radacct)) {
            $this->Flash->success(__('The radacct has been deleted.'));
        } else {
            $this->Flash->error(__('The radacct could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
