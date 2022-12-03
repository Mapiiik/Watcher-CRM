<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ContractStates Controller
 *
 * @property \App\Model\Table\ContractStatesTable $ContractStates
 * @method \App\Model\Entity\ContractState[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ContractStatesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'ContractStates.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => ['name' => 'ASC'],
            'conditions' => $conditions,
        ];

        $contractStates = $this->paginate($this->ContractStates);

        $this->set(compact('contractStates'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract State id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $contractState = $this->ContractStates->get($id, [
            'contain' => [
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
            ],
        ]);

        $this->set(compact('contractState'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $contractState = $this->ContractStates->newEmptyEntity();
        if ($this->request->is('post')) {
            $contractState = $this->ContractStates->patchEntity($contractState, $this->request->getData());
            if ($this->ContractStates->save($contractState)) {
                $this->Flash->success(__('The contract state has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract state could not be saved. Please, try again.'));
        }
        $creators = $this->ContractStates->Creators->find('list', ['limit' => 200])->all();
        $modifiers = $this->ContractStates->Modifiers->find('list', ['limit' => 200])->all();
        $this->set(compact('contractState', 'creators', 'modifiers'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Contract State id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $contractState = $this->ContractStates->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $contractState = $this->ContractStates->patchEntity($contractState, $this->request->getData());
            if ($this->ContractStates->save($contractState)) {
                $this->Flash->success(__('The contract state has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The contract state could not be saved. Please, try again.'));
        }
        $creators = $this->ContractStates->Creators->find('list', ['limit' => 200])->all();
        $modifiers = $this->ContractStates->Modifiers->find('list', ['limit' => 200])->all();
        $this->set(compact('contractState', 'creators', 'modifiers'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract State id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $contractState = $this->ContractStates->get($id);
        if ($this->ContractStates->delete($contractState)) {
            $this->Flash->success(__('The contract state has been deleted.'));
        } else {
            $this->Flash->error(__('The contract state could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
