<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Commissions Controller
 *
 * @property \App\Model\Table\CommissionsTable $Commissions
 */
class CommissionsController extends AppController
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
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Commissions.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $commissions = $this->paginate($this->Commissions->find(
            'all',
            contain: [],
            conditions: $conditions
        ));

        $this->set(compact('commissions'));
    }

    /**
     * View method
     *
     * @param string|null $id Commission id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $commission = $this->Commissions->get($id, contain: [
            'DealerCommissions' => ['Dealers'],
            'Contracts' => [
                'Customers',
                'ContractStates',
                'ServiceTypes',
                'InstallationAddresses',
                'InstallationTechnicians',
                'UninstallationTechnicians',
            ],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('commission'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $commission = $this->Commissions->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $commission = $this->Commissions->patchEntity($commission, $this->getRequest()->getData());
            if ($this->Commissions->save($commission)) {
                $this->Flash->success(__('The commission has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $commission->id]);
            }
            $this->Flash->error(__('The commission could not be saved. Please, try again.'));
        }
        $this->set(compact('commission'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Commission id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $commission = $this->Commissions->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $commission = $this->Commissions->patchEntity($commission, $this->getRequest()->getData());
            if ($this->Commissions->save($commission)) {
                $this->Flash->success(__('The commission has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $commission->id]);
            }
            $this->Flash->error(__('The commission could not be saved. Please, try again.'));
        }
        $this->set(compact('commission'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Commission id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $commission = $this->Commissions->get($id);
        if ($this->Commissions->delete($commission)) {
            $this->Flash->success(__('The commission has been deleted.'));
        } else {
            $this->flashValidationErrors($commission->getErrors());
            $this->Flash->error(__('The commission could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
