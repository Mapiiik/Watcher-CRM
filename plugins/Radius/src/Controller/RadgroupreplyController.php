<?php
declare(strict_types=1);

namespace Radius\Controller;

/**
 * Radgroupreply Controller
 *
 * @property \Radius\Model\Table\RadgroupreplyTable $Radgroupreply
 * @method \Radius\Model\Entity\Radgroupreply[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class RadgroupreplyController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $radgroupreplies = $this->paginate($this->Radgroupreply->find(
            'all',
            contain: [],
            conditions: []
        ));

        $this->set(compact('radgroupreplies'));
    }

    /**
     * View method
     *
     * @param string|null $id Radgroupreply id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $radgroupreply = $this->Radgroupreply->get($id, contain: ['Radusergroup']);

        $this->set(compact('radgroupreply'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radgroupreply = $this->Radgroupreply->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $radgroupreply = $this->Radgroupreply->patchEntity($radgroupreply, $this->getRequest()->getData());
            if ($this->Radgroupreply->save($radgroupreply)) {
                $this->Flash->success(__d('radius', 'The RADIUS group reply has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS group reply could not be saved. Please, try again.'));
        }
        $this->set(compact('radgroupreply'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radgroupreply id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $radgroupreply = $this->Radgroupreply->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $radgroupreply = $this->Radgroupreply->patchEntity($radgroupreply, $this->getRequest()->getData());
            if ($this->Radgroupreply->save($radgroupreply)) {
                $this->Flash->success(__d('radius', 'The RADIUS group reply has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('radius', 'The RADIUS group reply could not be saved. Please, try again.'));
        }
        $this->set(compact('radgroupreply'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radgroupreply id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $radgroupreply = $this->Radgroupreply->get($id);
        if ($this->Radgroupreply->delete($radgroupreply)) {
            $this->Flash->success(__d('radius', 'The RADIUS group reply has been deleted.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS group reply could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
