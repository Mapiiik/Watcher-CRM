<?php
declare(strict_types=1);

namespace Radius\Controller;

/**
 * Radreply Controller
 *
 * @property \Radius\Model\Table\RadreplyTable $Radreply
 * @method \Radius\Model\Entity\Radreply[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RadreplyController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $radreplies = $this->paginate($this->Radreply->find(
            'all',
            contain: [
                'Accounts',
            ],
            conditions: []
        ));

        $this->set(compact('radreplies'));
    }

    /**
     * View method
     *
     * @param string|null $id Radreply id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $radreply = $this->Radreply->get($id, contain: ['Accounts']);

        $this->set(compact('radreply'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radreply = $this->Radreply->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $radreply = $this->Radreply->patchEntity($radreply, $this->getRequest()->getData());
            if ($this->Radreply->save($radreply)) {
                $this->Flash->success(__d('radius', 'The RADIUS reply has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $radreply->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS reply could not be saved. Please, try again.'));
        }
        $accounts = $this->Radreply->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radreply', 'accounts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radreply id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $radreply = $this->Radreply->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $radreply = $this->Radreply->patchEntity($radreply, $this->getRequest()->getData());
            if ($this->Radreply->save($radreply)) {
                $this->Flash->success(__d('radius', 'The RADIUS reply has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS reply could not be saved. Please, try again.'));
        }
        $accounts = $this->Radreply->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radreply', 'accounts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radreply id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $radreply = $this->Radreply->get($id);
        if ($this->Radreply->delete($radreply)) {
            $this->Flash->success(__d('radius', 'The RADIUS reply has been deleted.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS reply could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
