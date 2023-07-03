<?php
declare(strict_types=1);

namespace Radius\Controller;

/**
 * Radacct Controller
 *
 * @property \Radius\Model\Table\RadacctTable $Radacct
 * @method \Radius\Model\Entity\Radacct[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $radaccts = $this->paginate($this->Radacct->find(
            'all',
            contain: [
                'Accounts',
            ],
            conditions: []
        ));

        $this->set(compact('radaccts'));
    }

    /**
     * View method
     *
     * @param string|null $id Radacct id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $radacct = $this->Radacct->get($id, contain: ['Accounts']);

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
                $this->Flash->success(__d('radius', 'The RADIUS accounting has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS accounting could not be saved. Please, try again.'));
        }
        $accounts = $this->Radacct->Accounts->find('list', ['keyField' => 'username', 'order' => 'username']);
        $this->set(compact('radacct', 'accounts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radacct id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $radacct = $this->Radacct->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $radacct = $this->Radacct->patchEntity($radacct, $this->request->getData());
            if ($this->Radacct->save($radacct)) {
                $this->Flash->success(__d('radius', 'The RADIUS accounting has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS accounting could not be saved. Please, try again.'));
        }
        $accounts = $this->Radacct->Accounts->find('list', ['keyField' => 'username', 'order' => 'username']);
        $this->set(compact('radacct', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radacct id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $radacct = $this->Radacct->get($id);
        if ($this->Radacct->delete($radacct)) {
            $this->Flash->success(__d('radius', 'The RADIUS accounting has been deleted.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS accounting could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
