<?php
declare(strict_types=1);

namespace Radius\Controller;

use Cake\Http\Response;

/**
 * Radcheck Controller
 *
 * @property \Radius\Model\Table\RadcheckTable $Radcheck
 */
class RadcheckController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $radchecks = $this->paginate($this->Radcheck->find(
            'all',
            contain: [
                'Accounts',
            ],
            conditions: [],
        ));

        $this->set(compact('radchecks'));
    }

    /**
     * View method
     *
     * @param string|null $id Radcheck id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $radcheck = $this->Radcheck->get($id, contain: ['Accounts']);

        $this->set(compact('radcheck'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $radcheck = $this->Radcheck->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $radcheck = $this->Radcheck->patchEntity($radcheck, $this->getRequest()->getData());
            if ($this->Radcheck->save($radcheck)) {
                $this->Flash->success(__d('radius', 'The RADIUS check has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $radcheck->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS check could not be saved. Please, try again.'));
        }
        $accounts = $this->Radcheck->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radcheck', 'accounts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Radcheck id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $radcheck = $this->Radcheck->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $radcheck = $this->Radcheck->patchEntity($radcheck, $this->getRequest()->getData());
            if ($this->Radcheck->save($radcheck)) {
                $this->Flash->success(__d('radius', 'The RADIUS check has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $radcheck->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS check could not be saved. Please, try again.'));
        }
        $accounts = $this->Radcheck->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radcheck', 'accounts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Radcheck id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $radcheck = $this->Radcheck->get($id);
        if ($this->Radcheck->delete($radcheck)) {
            $this->Flash->success(__d('radius', 'The RADIUS check has been deleted.'));
        } else {
            $this->flashValidationErrors($radcheck->getErrors());
            $this->Flash->error(__d('radius', 'The RADIUS check could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
