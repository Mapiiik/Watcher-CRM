<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * AccountingProfiles Controller
 *
 * @property \App\Model\Table\AccountingProfilesTable $AccountingProfiles
 */
class AccountingProfilesController extends AppController
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
                    'AccountingProfiles.name ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $accountingProfiles = $this->paginate($this->AccountingProfiles->find(
            'all',
            contain: [],
            conditions: $conditions,
        ));

        $this->set(compact('accountingProfiles'));
    }

    /**
     * View method
     *
     * @param string|null $id Accounting Profile id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $accountingProfile = $this->AccountingProfiles->get($id, contain: [
            'Customers' => [
                'AccountingProfiles',
                'Contracts',
                'IpAddresses' => [
                    'Contracts',
                ],
            ],
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('accountingProfile'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $accountingProfile = $this->AccountingProfiles->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $accountingProfile = $this->AccountingProfiles
                ->patchEntity($accountingProfile, $this->getRequest()->getData());
            if ($this->AccountingProfiles->save($accountingProfile)) {
                $this->Flash->success(__('The accounting profile has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $accountingProfile->id]);
            }
            $this->Flash->error(__('The accounting profile could not be saved. Please, try again.'));
        }
        $this->set(compact('accountingProfile'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Accounting Profile id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $accountingProfile = $this->AccountingProfiles->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $accountingProfile = $this->AccountingProfiles
                ->patchEntity($accountingProfile, $this->getRequest()->getData());
            if ($this->AccountingProfiles->save($accountingProfile)) {
                $this->Flash->success(__('The accounting profile has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $accountingProfile->id]);
            }
            $this->Flash->error(__('The accounting profile could not be saved. Please, try again.'));
        }
        $this->set(compact('accountingProfile'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Accounting Profile id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $accountingProfile = $this->AccountingProfiles->get($id);
        if ($this->AccountingProfiles->delete($accountingProfile)) {
            $this->Flash->success(__('The accounting profile has been deleted.'));
        } else {
            $this->flashValidationErrors($accountingProfile->getErrors());
            $this->Flash->error(__('The accounting profile could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
