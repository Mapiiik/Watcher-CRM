<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Commissions Controller
 *
 * @property \App\Model\Table\CommissionsTable $Commissions
 * @method \App\Model\Entity\Commission[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Commissions.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => ['name' => 'ASC'],
            'conditions' => $conditions,
        ];

        $commissions = $this->paginate($this->Commissions);

        $this->set(compact('commissions'));
    }

    /**
     * View method
     *
     * @param string|null $id Commission id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $commission = $this->Commissions->get($id, [
            'contain' => [
                'DealerCommissions' => ['Dealers'],
                'Contracts' => [
                    'Customers',
                    'ServiceTypes',
                    'InstallationAddresses',
                    'InstallationTechnicians',
                ],
                'Creators',
                'Modifiers',
            ],
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

                return $this->redirect(['action' => 'index']);
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
    public function edit($id = null)
    {
        $commission = $this->Commissions->get($id, [
            'contain' => [],
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $commission = $this->Commissions->patchEntity($commission, $this->getRequest()->getData());
            if ($this->Commissions->save($commission)) {
                $this->Flash->success(__('The commission has been saved.'));

                return $this->redirect(['action' => 'index']);
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
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $commission = $this->Commissions->get($id);
        if ($this->Commissions->delete($commission)) {
            $this->Flash->success(__('The commission has been deleted.'));
        } else {
            $this->Flash->error(__('The commission could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
