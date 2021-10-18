<?php
declare(strict_types=1);

namespace RADIUS\Controller;

use RADIUS\Controller\AppController;

/**
 * Accounts Controller
 *
 * @property \RADIUS\Model\Table\AccountsTable $Accounts
 * @method \RADIUS\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
            'contain' => ['Customers', 'Contracts', 'Radcheck', 'Radreply', 'Radusergroup', 'Radpostauth', 'Radacct'],
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

        if (isset($customer_id)) $account = $this->Accounts->patchEntity($account, ['customer_id' => $customer_id]);
        if (isset($contract_id)) $account = $this->Accounts->patchEntity($account, ['contract_id' => $contract_id]);
        
        if ($this->request->is('post')) {
            $account = $this->Accounts->patchEntity($account, $this->request->getData());

            // autogenerate related radcheck recors
            $account = $this->Accounts->patchEntity($account, ['radcheck' => $this->autoRadcheckData($account)]);

            // autogenerate related radreply recors
            $account = $this->Accounts->patchEntity($account, ['radreply' => $this->autoRadreplyData($account)]);
            
            if ($this->Accounts->save($account)) {
                $this->Flash->success(__('The account has been saved.'));

                return $this->redirect(['action' => 'view', $account->id]);
            }
            $this->Flash->error(__('The account could not be saved. Please, try again.'));
        }
        $customers = $this->Accounts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Accounts->Contracts->find('list', ['order' => 'number']);

        $new_username = '';
        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $contracts->where(['customer_id' => $customer_id]);

            // START find free username
            $customer = $this->Accounts->Customers->get($customer_id);
            $new_username = strtolower($this->squashCharacters($customer->last_name . '.' . $customer->first_name));

            $i = 1;
            $test_username = $new_username;
            while ($this->Accounts->exists(['username' => $test_username]))
            {
                $i++;
                $test_username = $new_username . '.' . $i;
            }
            $new_username = $test_username;
            unset($test_username);
            unset($i);
            // END find free login
        }
        if (isset($contract_id)) {
            $contracts->where(['id' => $contract_id]);
        }
        
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

            // autogenerate related radcheck recors
            $account = $this->Accounts->patchEntity($account, ['radcheck' => $this->autoRadcheckData($account)]);
            
            if ($this->Accounts->save($account)) {
                $this->Flash->success(__('The account has been saved.'));

                return $this->redirect(['action' => 'view', $account->id]);
            }
            debug($account);
            $this->Flash->error(__('The account could not be saved. Please, try again.'));
        }
        $customers = $this->Accounts->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $contracts = $this->Accounts->Contracts->find('list', ['order' => 'number']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $contracts->where(['customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['id' => $contract_id]);
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
            $this->Flash->success(__('The account has been deleted.'));
        } else {
            $this->Flash->error(__('The account could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
    
    private function autoRadcheckData(\RADIUS\Model\Entity\Account $account): array
    {
        $radcheck = [];
        $radcheck[] = $this->getTableLocator()->get('RADIUS.Radcheck')
            ->findOrCreate([
                'username' => $account->username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $account->password,
            ])
            ->toArray();
        if (!$account->active) {
            $radcheck[] = $this->getTableLocator()->get('RADIUS.Radcheck')
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

    private function autoRadreplyData(\RADIUS\Model\Entity\Account $account): array
    {
        $contract = $this->getTableLocator()->get('Contracts')->get($account->contract_id, [
            'contain' => ['Ips'],
        ]);
        
        $radreply = [];
        foreach ($contract->ips as $ip)
        {
            @list($address, $mask) = explode('/', $ip->ip);

            if ($addressx = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            {
                if ($maskx = filter_var($mask, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 32))))
                {
                    $radreply[] = $this->getTableLocator()->get('RADIUS.Radreply')
                        ->findOrCreate([
                            'username' => $account->username,
                            'attribute' => 'Framed-Route',
                            'op' => '=',
                            'value' => $addressx . '/' . $maskx,
                        ])
                        ->toArray();
                }
                else
                {
                    $radreply[] = $this->getTableLocator()->get('RADIUS.Radreply')
                        ->findOrCreate([
                            'username' => $account->username,
                            'attribute' => 'Framed-IP-Address',
                            'op' => '=',
                            'value' => $addressx,
                        ])
                        ->toArray();
                }
            }
            if ($addressx = filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            {
                if ($maskx = filter_var($mask, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 128))))
                {
                    $radreply[] = $this->getTableLocator()->get('RADIUS.Radreply')
                        ->findOrCreate([
                            'username' => $account->username,
                            'attribute' => 'Framed-IPv6-Prefix',
                            'op' => '=',
                            'value' => $addressx . '/' . $maskx,
                        ])
                        ->toArray();
                }
                else
                {
                        $radius->network->ipv6->address[] = $addressx;
                    $radreply[] = $this->getTableLocator()->get('RADIUS.Radreply')
                        ->findOrCreate([
                            'username' => $account->username,
                            'attribute' => 'Framed-IPv6-Address',
                            'op' => '=',
                            'value' => $addressx . '/' . $maskx,
                        ])
                        ->toArray();
                }
            }            
        }
        return $radreply;
    }
}
