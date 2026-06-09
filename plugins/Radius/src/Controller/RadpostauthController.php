<?php
declare(strict_types=1);

namespace Radius\Controller;

use Cake\Http\Response;

/**
 * Radpostauth Controller
 *
 * @property \Radius\Model\Table\RadpostauthTable $Radpostauth
 */
class RadpostauthController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $radpostauths = $this->paginate($this->Radpostauth->find(
            'all',
            contain: [
                'Accounts',
            ],
            conditions: [],
        ));

        $this->set(compact('radpostauths'));
    }

    /**
     * View method
     *
     * @param string|null $id Radpostauth id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $radpostauth = $this->Radpostauth->get($id, contain: ['Accounts']);

        $this->set(compact('radpostauth'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $radpostauth = $this->Radpostauth->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $radpostauth = $this->Radpostauth->patchEntity($radpostauth, $this->getRequest()->getData());
            if ($this->Radpostauth->save($radpostauth)) {
                $this->Flash->success(__d('radius', 'The RADIUS post authentication has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $radpostauth->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS post authentication could not be saved. Please, try again.'));
        }
        $accounts = $this->Radpostauth->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radpostauth', 'accounts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $radpostauth = $this->Radpostauth->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $radpostauth = $this->Radpostauth->patchEntity($radpostauth, $this->getRequest()->getData());
            if ($this->Radpostauth->save($radpostauth)) {
                $this->Flash->success(__d('radius', 'The RADIUS post authentication has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $radpostauth->id]);
            }
            $this->Flash->error(__d('radius', 'The RADIUS post authentication could not be saved. Please, try again.'));
        }
        $accounts = $this->Radpostauth->Accounts->find('list', keyField: 'username', order: [
            'username',
        ]);
        $this->set(compact('radpostauth', 'accounts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Radpostauth id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $radpostauth = $this->Radpostauth->get($id);
        if ($this->Radpostauth->delete($radpostauth)) {
            $this->Flash->success(__d('radius', 'The RADIUS post authentication has been deleted.'));
        } else {
            $this->flashValidationErrors($radpostauth->getErrors());
            $this->Flash->error(
                __d('radius', 'The RADIUS post authentication could not be deleted. Please, try again.'),
            );
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
