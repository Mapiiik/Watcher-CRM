<?php
declare(strict_types=1);

namespace Radius\Controller;

use Cake\Database\Query;
use Cake\I18n\FrozenDate;

/**
 * Accounts Controller
 *
 * @property \Radius\Model\Table\AccountsTable $Accounts
 * @method \Radius\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AccountsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['Accounts.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Accounts.contract_id' => $contract_id];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts'],
            'conditions' => $conditions,
            'order' => [
                'Accounts.id' => 'DESC',
            ],
        ];
        $accounts = $this->paginate($this->Accounts);

        $this->set(compact('accounts'));
    }

    /**
     * View method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $account = $this->Accounts->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Radcheck',
                'Radreply',
                'Radusergroup',
            ],
        ]);

        $this->set(compact('account'));
    }

    /**
     * Monitoring method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function monitoring($id = null)
    {
        $account = $this->Accounts->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Radacct' => ['sort' => ['Radacct.acctstarttime' => 'DESC']],
                'Radpostauth' => ['sort' => ['Radpostauth.authdate' => 'DESC']],
            ],
        ]);

        $this->set(compact('account'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $account = $this->Accounts->newEmptyEntity();

        if (isset($customer_id)) {
            $account->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $account->contract_id = $contract_id;
        }

        if ($this->request->is('post')) {
            $account = $this->Accounts->patchEntity($account, $this->request->getData());

            if ($this->request->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if (!$account->hasErrors()) {
                    // autogenerate related records
                    $account = $this->Accounts->patchEntity($account, [
                        'radcheck' => $this->autoRadcheckData($account),
                        'radreply' => $this->autoRadreplyData($account),
                        'radusergroup' => $this->autoRadusergroupData($account),
                    ]);
                }

                if ($this->Accounts->save($account)) {
                    $this->Flash->success(__d('radius', 'The RADIUS account has been saved.'));

                    return $this->redirect(['action' => 'view', $account->id]);
                }
                $this->Flash->error(__d('radius', 'The RADIUS account could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Accounts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Accounts->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($account->customer_id)) {
            $contracts->where(['Contracts.customer_id' => $account->customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        // START find free username
        $new_username = '';
        if (isset($account->customer_id) && isset($account->contract_id)) {
            $customer = $this->Accounts->Customers->get($account->customer_id);
            $contract = $this->Accounts->Contracts->get($account->contract_id);

            if ($customer->id == $contract->customer_id) {
                // clear request data for username if empty
                if (empty($this->request->getData('username'))) {
                    $this->request = $this->request->withoutData('username');
                }

                if (empty($customer->company)) {
                    $new_username = strtolower($this->removeAccents(
                        $customer->last_name . '.' . $customer->first_name
                    ));
                    $new_username = strtr($new_username, [' - ' => '-', ' ' => '-']);
                } else {
                    $new_username = strtolower($this->removeAccents($customer->company));
                    $new_username = strtr($new_username, [' - ' => '-', ' ' => '-', '.' => '', ',' => '']);
                }
                //$new_username = $contract->number . '-' . $new_username;

                $i = 1;
                $test_username = $new_username;
                while ($this->Accounts->exists(['username' => $test_username])) {
                    $i++;
                    $test_username = $new_username . '.' . $i;
                }
                $new_username = $test_username;
                unset($test_username);
                unset($i);
            }
        }
        // END find free username

        $this->set(compact('account', 'customers', 'contracts'));

        // new available login
        $this->set('new_username', $new_username);

        // generate new password
        $this->set('new_password', $this->generatePassword(10));
    }

    /**
     * Edit method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->request->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->request->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $account = $this->Accounts->get($id, [
            'contain' => ['Radcheck'],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $account = $this->Accounts->patchEntity($account, $this->request->getData());

            if ($this->request->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if (!$account->hasErrors()) {
                    // autogenerate related records
                    $account = $this->Accounts->patchEntity($account, [
                        'radcheck' => $this->autoRadcheckData($account),
                    ]);
                }

                if ($this->Accounts->save($account)) {
                    $this->Flash->success(__d('radius', 'The RADIUS account has been saved.'));

                    return $this->redirect(['action' => 'view', $account->id]);
                }
                $this->Flash->error(__d('radius', 'The RADIUS account could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Accounts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Accounts->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($account->customer_id)) {
            $contracts->where(['Contracts.customer_id' => $account->customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $this->set(compact('account', 'customers', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $account = $this->Accounts->get($id);
        if ($this->Accounts->delete($account)) {
            $this->Flash->success(__d('radius', 'The RADIUS account has been deleted.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Update method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects always to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function updateRelatedRecords($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $account = $this->Accounts->get($id, [
            'contain' => ['Radcheck', 'Radreply', 'Radusergroup'],
        ]);

        // autogenerate related records
        $account = $this->Accounts->patchEntity($account, [
            'radcheck' => $this->autoRadcheckData($account),
            'radreply' => $this->autoRadreplyData($account),
            'radusergroup' => $this->autoRadusergroupData($account),
        ]);

        if ($this->Accounts->save($account)) {
            $this->Flash->success(__d('radius', 'The RADIUS account has been updated.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS account could not be updated. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $account->id]);
    }

    /**
     * generate data for radcheck table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array
     */
    private function autoRadcheckData(\Radius\Model\Entity\Account $account): array
    {
        $radcheck = [];

        $radcheck[] = $this->fetchTable('Radius.Radcheck')
            ->findOrCreate([
                'username' => $account->username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $account->password,
            ])
            ->toArray();
        if (!$account->active) {
            $radcheck[] = $this->fetchTable('Radius.Radcheck')
                ->findOrCreate([
                    'username' => $account->username,
                    'attribute' => 'Auth-Type',
                    'op' => ':=',
                    'value' => 'Reject',
                ])
                ->toArray();
        }

        return $radcheck;
    }

    /**
     * generate data for radreply table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array
     */
    private function autoRadreplyData(\Radius\Model\Entity\Account $account): array
    {
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, [
            'contain' => ['Ips', 'IpNetworks'],
        ]);

        $radreply = [];

        foreach ($contract->ips as $ip) {
            @[$address, $mask] = explode('/', $ip->ip); // phpcs:ignore

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrCreate([
                        'username' => $account->username,
                        'attribute' => 'Framed-IP-Address',
                        'op' => '=',
                        'value' => $address,
                    ])
                    ->toArray();
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrCreate([
                        'username' => $account->username,
                        'attribute' => 'Framed-IPv6-Address',
                        'op' => '=',
                        'value' => $address,
                    ])
                    ->toArray();
            }
        }

        foreach ($contract->ip_networks as $ipNetwork) {
            @[$address, $mask] = explode('/', $ipNetwork->ip_network); // phpcs:ignore

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrCreate([
                        'username' => $account->username,
                        'attribute' => 'Framed-Route',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ])
                    ->toArray();
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrCreate([
                        'username' => $account->username,
                        'attribute' => 'Framed-IPv6-Prefix',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ])
                    ->toArray();
            }
        }

        if (empty($radreply)) {
            // return current radusergroup records
            if (is_array($account->radreply)) {
                foreach ($account->radreply as $current_radreply) {
                    $radreply[] = $current_radreply->toArray();
                }
            }
            $this->Flash->warning(
                __d('radius', 'The RADIUS replies could not be found automatically. Please, set it manually.')
            );
        }

        return $radreply;
    }

    /**
     * generate data for radusergroup table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array
     */
    private function autoRadusergroupData(\Radius\Model\Entity\Account $account): array
    {
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, [
            'contain' => [
                'Billings' => [
                    'queryBuilder' => function (Query $q) {
                        return $q->where([
                            'Queues.name IS NOT NULL',
                            'Billings.billing_from <=' => (new FrozenDate())->addMonths(1),
                        ])
                        ->andWhere([
                            'OR' => [
                                'Billings.billing_until IS NULL',
                                'Billings.billing_until >=' => new FrozenDate(),
                            ],
                        ])
                        ->order(['Billings.billing_from' => 'ASC']);
                    },
                    'Services' => 'Queues',
                ],
            ],
        ]);

        $radusergroup = [];

        if (isset($contract->billings[0])) {
            // return radusergroup record with current (or the near future) queue name as groupname
            $radusergroup[] = $this->fetchTable('Radius.Radusergroup')
                ->findOrCreate([
                    'username' => $account->username,
                    'groupname' => $contract->billings[0]->service->queue->name,
                    'priority' => 0,
                ])
                ->toArray();
        }

        if (empty($radusergroup)) {
            // return current radusergroup records if exists
            if (is_array($account->radusergroup)) {
                foreach ($account->radusergroup as $current_radusergroup) {
                    $radusergroup[] = $current_radusergroup->toArray();
                }
            }
            $this->Flash->warning(
                __d('radius', 'The RADIUS user groups could not be found automatically. Please, set it manually.')
            );
        }

        return $radusergroup;
    }
}
