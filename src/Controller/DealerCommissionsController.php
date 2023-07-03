<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * DealerCommissions Controller
 *
 * @property \App\Model\Table\DealerCommissionsTable $DealerCommissions
 * @method \App\Model\Entity\DealerCommission[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class DealerCommissionsController extends AppController
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
            'contain' => ['Dealers', 'Commissions'],
            'order' => ['id' => 'DESC'],
            'conditions' => $conditions,
        ];

        $dealerCommissions = $this->paginate($this->DealerCommissions);

        $this->set(compact('dealerCommissions'));
    }

    /**
     * View method
     *
     * @param string|null $id Dealer Commission id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $dealerCommission = $this->DealerCommissions->get($id, contain: [
            'Dealers',
            'Commissions',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('dealerCommission'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $dealerCommission = $this->DealerCommissions->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $dealerCommission = $this->DealerCommissions
                ->patchEntity($dealerCommission, $this->getRequest()->getData());

            if ($this->DealerCommissions->save($dealerCommission)) {
                $this->Flash->success(__('The dealer commission has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The dealer commission could not be saved. Please, try again.'));
        }
        $dealers = $this->DealerCommissions->Dealers
            ->find('all')
            ->where(['dealer' => 1]) // only current dealers
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->DealerCommissions->Commissions->find('list', [
            'order' => ['name'],
        ]);
        $this->set(compact('dealerCommission', 'dealers', 'commissions'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Dealer Commission id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $dealerCommission = $this->DealerCommissions->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $dealerCommission = $this->DealerCommissions
                ->patchEntity($dealerCommission, $this->getRequest()->getData());

            if ($this->DealerCommissions->save($dealerCommission)) {
                $this->Flash->success(__('The dealer commission has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The dealer commission could not be saved. Please, try again.'));
        }
        $dealers = $this->DealerCommissions->Dealers
            ->find('all')
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->DealerCommissions->Commissions->find('list', [
            'order' => ['name'],
        ]);
        $this->set(compact('dealerCommission', 'dealers', 'commissions'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Dealer Commission id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $dealerCommission = $this->DealerCommissions->get($id);
        if ($this->DealerCommissions->delete($dealerCommission)) {
            $this->Flash->success(__('The dealer commission has been deleted.'));
        } else {
            $this->Flash->error(__('The dealer commission could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
