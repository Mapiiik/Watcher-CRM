<?php
declare(strict_types=1);

namespace Radius\Controller;

/**
 * Radpostauth Controller
 *
 * @property \Radius\Model\Table\RadpostauthTable $Radpostauth
 * @method \Radius\Model\Entity\Radpostauth[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RadpostauthController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $radpostauths = $this->paginate($this->Radpostauth->find(
            'all',
            contain: [
                'Accounts',
            ],
            conditions: []
        ));

        $this->set(compact('radpostauths'));
    }

    /**
     * View method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $radpostauth = $this->Radpostauth->get($id, contain: ['Accounts']);

        $this->set(compact('radpostauth'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radpostauth = $this->Radpostauth->newEmptyEntity();
        if ($this->request->is('post')) {
            $radpostauth = $this->Radpostauth->patchEntity($radpostauth, $this->request->getData());
            if ($this->Radpostauth->save($radpostauth)) {
                $this->Flash->success(__d('radius', 'The RADIUS post authentication has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS post authentication could not be saved. Please, try again.'));
        }
        $accounts = $this->Radpostauth->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radpostauth', 'accounts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $radpostauth = $this->Radpostauth->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $radpostauth = $this->Radpostauth->patchEntity($radpostauth, $this->request->getData());
            if ($this->Radpostauth->save($radpostauth)) {
                $this->Flash->success(__d('radius', 'The RADIUS post authentication has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS post authentication could not be saved. Please, try again.'));
        }
        $accounts = $this->Radpostauth->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radpostauth', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $radpostauth = $this->Radpostauth->get($id);
        if ($this->Radpostauth->delete($radpostauth)) {
            $this->Flash->success(__d('radius', 'The RADIUS post authentication has been deleted.'));
        } else {
            $this->Flash->error(
                __d('radius', 'The RADIUS post authentication could not be deleted. Please, try again.')
            );
        }

        return $this->redirect(['action' => 'index']);
    }
}
