<?php
declare(strict_types=1);

namespace RADIUS\Controller;

/**
 * Radpostauth Controller
 *
 * @property \RADIUS\Model\Table\RadpostauthTable $Radpostauth
 * @method \RADIUS\Model\Entity\Radpostauth[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $this->paginate = [
            'contain' => ['Accounts'],
        ];
        $radpostauth = $this->paginate($this->Radpostauth);

        $this->set(compact('radpostauths'));
    }

    /**
     * View method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $radpostauth = $this->Radpostauth->get($id, [
            'contain' => ['Accounts'],
        ]);

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
                $this->Flash->success(__('The radpostauth has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radpostauth could not be saved. Please, try again.'));
        }
        $accounts = $this->Radpostauth->Accounts->find('list', ['keyField' => 'username', 'order' => 'username']);
        $this->set(compact('radpostauth', 'accounts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $radpostauth = $this->Radpostauth->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $radpostauth = $this->Radpostauth->patchEntity($radpostauth, $this->request->getData());
            if ($this->Radpostauth->save($radpostauth)) {
                $this->Flash->success(__('The radpostauth has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The radpostauth could not be saved. Please, try again.'));
        }
        $accounts = $this->Radpostauth->Accounts->find('list', ['keyField' => 'username', 'order' => 'username']);
        $this->set(compact('radpostauth', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $radpostauth = $this->Radpostauth->get($id);
        if ($this->Radpostauth->delete($radpostauth)) {
            $this->Flash->success(__('The radpostauth has been deleted.'));
        } else {
            $this->Flash->error(__('The radpostauth could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
