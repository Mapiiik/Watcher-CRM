<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * ContractStates Controller
 *
 * @property \App\Model\Table\ContractStatesTable $ContractStates
 */
class ContractStatesController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ContractStates.name ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $contractStates = $this->paginate($this->ContractStates->find(
            'all',
            contain: [],
            conditions: $conditions,
        ));

        $this->set(compact('contractStates'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract State id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $contractState = $this->ContractStates->get($id, contain: [
            'Contracts' => [
                'Commissions',
                'Customers',
                'InstallationAddresses',
                'InstallationTechnicians',
                'UninstallationTechnicians',
                'ServiceTypes',
            ],
            'Creators',
            'Modifiers',
            'RequiresOpenTaskTypes',
        ]);

        $this->set(compact('contractState'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $contractState = $this->ContractStates->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $contractState = $this->ContractStates->patchEntity($contractState, $this->getRequest()->getData());
            if ($this->ContractStates->save($contractState)) {
                $this->Flash->success(__('The contract state has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $contractState->id]);
            }
            $this->Flash->error(__('The contract state could not be saved. Please, try again.'));
        }

        $requiresOpenTaskTypes = $this->ContractStates->RequiresOpenTaskTypes->find('list', order: [
            'name',
        ]);

        $this->set(compact('contractState', 'requiresOpenTaskTypes'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Contract State id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $contractState = $this->ContractStates->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $contractState = $this->ContractStates->patchEntity($contractState, $this->getRequest()->getData());
            if ($this->ContractStates->save($contractState)) {
                $this->Flash->success(__('The contract state has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $contractState->id]);
            }
            $this->Flash->error(__('The contract state could not be saved. Please, try again.'));
        }

        $requiresOpenTaskTypes = $this->ContractStates->RequiresOpenTaskTypes->find('list', order: [
            'name',
        ]);

        $this->set(compact('contractState', 'requiresOpenTaskTypes'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract State id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $contractState = $this->ContractStates->get($id);
        if ($this->ContractStates->delete($contractState)) {
            $this->Flash->success(__('The contract state has been deleted.'));
        } else {
            $this->flashValidationErrors($contractState->getErrors());
            $this->Flash->error(__('The contract state could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
