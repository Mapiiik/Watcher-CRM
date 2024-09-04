<?php
declare(strict_types=1);

namespace Radius\Controller;

/**
 * Radusergroup Controller
 *
 * @property \Radius\Model\Table\RadusergroupTable $Radusergroup
 */
class RadusergroupController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $radusergroups = $this->paginate($this->Radusergroup->find(
            'all',
            contain: [
                'Accounts',
            ],
            conditions: []
        ));

        $this->set(compact('radusergroups'));
    }

    /**
     * View method
     *
     * @param string|null $id Radusergroup id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $radusergroup = $this->Radusergroup->get($id, contain: ['Accounts', 'Radgroupcheck', 'Radgroupreply']);

        $this->set(compact('radusergroup'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $radusergroup = $this->Radusergroup->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $radusergroup = $this->Radusergroup->patchEntity($radusergroup, $this->getRequest()->getData());
            if ($this->Radusergroup->save($radusergroup)) {
                $this->Flash->success(__d('radius', 'The RADIUS user group has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $radusergroup->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS user group could not be saved. Please, try again.'));
        }
        $accounts = $this->Radusergroup->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $groupnames = $this->Radusergroup->Radgroupreply->find(
            'list',
            keyField: 'groupname',
            valueField: 'groupname',
            group: [
                'groupname',
            ],
            order: [
                'groupname',
            ],
        );
        $this->set(compact('radusergroup', 'accounts', 'groupnames'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Radusergroup id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $radusergroup = $this->Radusergroup->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $radusergroup = $this->Radusergroup->patchEntity($radusergroup, $this->getRequest()->getData());
            if ($this->Radusergroup->save($radusergroup)) {
                $this->Flash->success(__d('radius', 'The RADIUS user group has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $radusergroup->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS user group could not be saved. Please, try again.'));
        }
        $accounts = $this->Radusergroup->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $groupnames = $this->Radusergroup->Radgroupreply->find(
            'list',
            keyField: 'groupname',
            valueField: 'groupname',
            group: [
                'groupname',
            ],
            order: [
                'groupname',
            ],
        );
        $this->set(compact('radusergroup', 'accounts', 'groupnames'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Radusergroup id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $radusergroup = $this->Radusergroup->get($id);
        if ($this->Radusergroup->delete($radusergroup)) {
            $this->Flash->success(__d('radius', 'The RADIUS user group has been deleted.'));
        } else {
            $this->flashValidationErrors($radusergroup->getErrors());
            $this->Flash->error(__d('radius', 'The RADIUS user group could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
