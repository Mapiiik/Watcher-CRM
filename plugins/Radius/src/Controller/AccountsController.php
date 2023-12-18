<?php
declare(strict_types=1);

namespace Radius\Controller;

use App\Strings;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;
use Mapik\RadiusClient\Client;
use Mapik\RadiusClient\Exceptions\ClientException;
use Mapik\RadiusClient\Packet;
use Mapik\RadiusClient\PacketType;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radacct;

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
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions += ['Accounts.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['Accounts.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Accounts.username ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $accounts = $this->paginate($this->Accounts->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions
        ));

        $this->set(compact('accounts'));
    }

    /**
     * View method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $account = $this->Accounts->get($id, contain: [
            'Contracts',
            'Customers',
            'Radcheck',
            'Radreply',
            'Radusergroup',
            'Creators',
            'Modifiers',
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
    public function monitoring(?string $id = null)
    {
        $account = $this->Accounts->get($id, contain: [
            'Contracts',
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $radaccts = $this->paginate(
            $this->Accounts->Radacct
                ->find()
                ->where([
                    'Radacct.username' => $account->username,
                ]),
            [
                'scope' => 'radacct',
                'order' => [
                    'acctstarttime' => 'DESC',
                ],
            ]
        );

        $radpostauths = $this->paginate(
            $this->Accounts->Radpostauth
                ->find()
                ->where([
                    'Radpostauth.username' => $account->username,
                ]),
            [
                'scope' => 'radpostauth',
                'order' => [
                    'authdate' => 'DESC',
                ],
            ]
        );

        $details = $this->getRequest()->getQuery('show_details') == true;

        $this->set(compact('account', 'details', 'radaccts', 'radpostauths'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $account = $this->Accounts->newEmptyEntity();

        if (isset($this->customer_id)) {
            $account->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $account->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $account = $this->Accounts->patchEntity($account, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if (!$account->hasErrors()) {
                    // autogenerate related records
                    $account->radcheck = $this->autoRadcheckData($account);
                    $account->radreply = $this->autoRadreplyData($account);
                    $account->radusergroup = $this->autoRadusergroupData($account);
                }

                if ($this->Accounts->save($account)) {
                    $this->Flash->success(__d('radius', 'The RADIUS account has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $account->id]);
                }
                $this->Flash->error(__d('radius', 'The RADIUS account could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Accounts->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Accounts->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($account->customer_id)) {
            $contracts->where(['Contracts.customer_id' => $account->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        // START find free username
        $new_username = '';
        if (isset($account->customer_id) && isset($account->contract_id)) {
            $customer = $this->Accounts->Customers->get($account->customer_id);
            $contract = $this->Accounts->Contracts->get($account->contract_id);

            if ($customer->id == $contract->customer_id) {
                // clear request data for username if empty
                if (empty($this->getRequest()->getData('username'))) {
                    $this->setRequest($this->getRequest()->withoutData('username'));
                }

                if (empty($customer->company)) {
                    $new_username = strtolower(Strings::removeAccents(
                        $customer->last_name . '.' . $customer->first_name
                    ));
                    $new_username = strtr($new_username, [' - ' => '-', ' ' => '-']);
                } else {
                    $new_username = strtolower(Strings::removeAccents($customer->company));
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
        $this->set('new_password', Strings::generatePassword(10));
    }

    /**
     * Edit method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $account = $this->Accounts->get($id, contain: [
            'Radcheck',
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $account = $this->Accounts->patchEntity($account, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if (!$account->hasErrors()) {
                    // autogenerate related records
                    $account->radcheck = $this->autoRadcheckData($account);
                }

                if ($this->Accounts->save($account)) {
                    $this->Flash->success(__d('radius', 'The RADIUS account has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $account->id]);
                }
                $this->Flash->error(__d('radius', 'The RADIUS account could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Accounts->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Accounts->Contracts->find(
            'list',
            contain: [
                'ServiceTypes',
                'InstallationAddresses',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($account->customer_id)) {
            $contracts->where(['Contracts.customer_id' => $account->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
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
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $account = $this->Accounts->get($id);
        if ($this->Accounts->delete($account)) {
            $this->Flash->success(__d('radius', 'The RADIUS account has been deleted.'));
        } else {
            $this->flashValidationErrors($account->getErrors());
            $this->Flash->error(__d('radius', 'The RADIUS account could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Send disconnect request method
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return bool Returns true if the disconnection was successful.
     */
    private function sendDisconnectRequest(Radacct $session): bool
    {
        $disconnected = false;

        $client = new Client('udp://' . $session->nasipaddress . ':1700', /* timeout */ 3);
        try {
            $response = $client->send(
                new Packet(PacketType::DISCONNECT_REQUEST(), /* secret */ env('RADIUS_SECRET'), [
                    'User-Name' => $session->username,
                    'Acct-Session-Id' => $session->acctsessionid,
                    'Framed-IP-Address' => $session->framedipaddress,
                    'NAS-IP-Address' => $session->nasipaddress,
                ])
            );
        } catch (ClientException $e) {
            $this->Flash->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $e->getMessage()
            ));

            // skip further processing and return false
            return false;
        }

        // detect response type
        switch ($response->getType()) {
            case PacketType::COA_ACK():
                $result = 'CoA-ACK';
                $disconnected = true;
                break;
            case PacketType::DISCONNECT_ACK():
                $result = 'Disconnect-ACK';
                $disconnected = true;
                break;
            case PacketType::COA_NAK():
                $result = 'CoA-NAK';
                break;
            case PacketType::DISCONNECT_NAK():
                $result = 'Disconnect-NAK';
                break;
            default:
                $result = 'Unsupported reply';
        }

        // detect error causes
        $errors = [];
        $attributes = $response->getAttributes();
        if (isset($attributes['Error-Cause']) && is_array($attributes['Error-Cause'])) {
            foreach ($attributes['Error-Cause'] as $error_code) {
                switch ($error_code) {
                    case 401:
                        $errors[] = 'Unsupported Attribute';
                        break;
                    case 402:
                        $errors[] = 'Missing Attribute';
                        break;
                    case 403:
                        $errors[] = 'NAS Identification Mismatch';
                        break;
                    case 404:
                        $errors[] = 'Invalid Request';
                        break;
                    case 405:
                        $errors[] = 'Unsupported Service';
                        break;
                    case 406:
                        $errors[] = 'Unsupported Extension';
                        break;
                    case 407:
                        $errors[] = 'Invalid Attribute Value';
                        break;
                    case 501:
                        $errors[] = 'Administratively Prohibited';
                        break;
                    case 502:
                        $errors[] = 'Request Not Routable (Proxy)';
                        break;
                    case 503:
                        $errors[] = 'Session Context Not Found';
                        break;
                    case 504:
                        $errors[] = 'Session Context Not Removable';
                        break;
                    case 505:
                        $errors[] = 'Other Proxy Processing Error';
                        break;
                    case 506:
                        $errors[] = 'Resources Unavailable';
                        break;
                    case 507:
                        $errors[] = 'Request Initiated';
                        break;
                    case 508:
                        $errors[] = 'Multiple Session Selection Unsupported';
                        break;
                    default:
                        $errors[] = 'Unsupported Error-Cause';
                }
            }
        }
        $error = implode(', ', $errors);

        if ($disconnected) {
            $this->Flash->success(__d(
                'radius',
                'The RADIUS session for {0} started on {1} has been disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $error ? $result . ' - ' . $error : $result
            ));

            return true;
        } else {
            $this->Flash->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $error ? $result . ' - ' . $error : $result
            ));

            return false;
        }
    }

    /**
     * Disconnect request method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects always to monitoring.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function disconnectRequest(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);
        $account = $this->Accounts->get($id, contain: [
            'Radacct' => [
                'conditions' => [
                    'Radacct.acctstoptime IS' => null,
                ],
            ],
        ]);

        if (empty($account->radacct)) {
            $this->Flash->warning(__d(
                'radius',
                'No active RADIUS session for {0} found.',
                $account->username
            ));

            return $this->redirect(['action' => 'monitoring', $account->id]);
        }

        foreach ($account->radacct as $session) {
            $this->sendDisconnectRequest($session);
        }

        // wait one second for RADIUS records to update
        sleep(1);

        return $this->redirect($this->referer(['action' => 'monitoring', $account->id]));
    }

    /**
     * Update related records method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects always to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function updateRelatedRecords(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);
        $account = $this->Accounts->get($id, contain: [
            'Radcheck',
            'Radreply',
            'Radusergroup',
        ]);

        // autogenerate related records
        $account->radcheck = $this->autoRadcheckData($account);
        $account->radreply = $this->autoRadreplyData($account);
        $account->radusergroup = $this->autoRadusergroupData($account);

        if ($this->Accounts->save($account)) {
            $this->Flash->success(__d('radius', 'The RADIUS account has been updated.'));
        } else {
            $this->Flash->error(__d('radius', 'The RADIUS account could not be updated. Please, try again.'));
        }

        return $this->redirect($this->referer(['action' => 'view', $account->id]));
    }

    /**
     * Update related records for all accounts method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function updateRelatedRecordsForAllAccounts()
    {
        if ($this->getRequest()->is(['post'])) {
            $accountsQuery = $this->Accounts->find('all', contain: [
                'Radcheck',
                'Radreply',
                'Radusergroup',
            ]);

            switch ($this->getRequest()->getData('state')) {
                case 'active':
                    $accountsQuery->where(['active' => true]);
                    break;
                case 'inactive':
                    $accountsQuery->where(['active' => false]);
                    break;
            }

            $processed = 0;
            $modified = 0;
            $failed = 0;

            $something_to_do = false;

            /** @var iterable<\Radius\Model\Entity\Account> $accounts */
            $accounts = $accountsQuery->all();

            foreach ($accounts as $account) {
                $is_modified = false;

                // autogenerate related records
                if ($this->getRequest()->getData('radcheck') == '1') {
                    $something_to_do = true;

                    $radcheck = $this->autoRadcheckData($account);
                    sort($radcheck);
                    sort($account->radcheck);
                    if ($account->radcheck != $radcheck) {
                        $is_modified = true;
                        $account->radcheck = $radcheck;
                    }
                    unset($radcheck);
                }
                if ($this->getRequest()->getData('radreply') == '1') {
                    $something_to_do = true;

                    $radreply = $this->autoRadreplyData($account);
                    sort($radreply);
                    sort($account->radreply);
                    if ($account->radreply != $radreply) {
                        $is_modified = true;
                        $account->radreply = $radreply;
                    }
                    unset($radreply);
                }
                if ($this->getRequest()->getData('radusergroup') == '1') {
                    $something_to_do = true;

                    $radusergroup = $this->autoRadusergroupData($account);
                    sort($radusergroup);
                    sort($account->radusergroup);
                    if ($account->radusergroup != $radusergroup) {
                        $is_modified = true;
                        $account->radusergroup = $radusergroup;
                    }
                    unset($radusergroup);
                }

                // stop processing if there is nothing to do
                if (!$something_to_do) {
                    $this->Flash->warning(__d('radius', 'Nothing has been selected for update.'));

                    return;
                }

                $processed++;
                if ($is_modified) {
                    // save modified data
                    if ($this->Accounts->save($account) === false) {
                        $failed++;

                        $this->Flash->error(
                            __d(
                                'radius',
                                'The RADIUS account {0} could not be updated. Please, try again.',
                                $account->username
                            )
                        );
                        Log::warning('The RADIUS account ' . $account->username . ' could not be updated.');
                    } else {
                        $modified++;

                        // Send RADIUS disconnect request for modified
                        if ($this->getRequest()->getData('reconnect_modified_accounts') == '1') {
                            $radaccts = $this->Accounts->Radacct
                                ->find()
                                ->where([
                                    'Radacct.username' => $account->username,
                                    'Radacct.acctstoptime IS' => null,
                                ])
                                ->all()
                                ->toArray();

                            if (empty($radaccts)) {
                                $this->Flash->warning(__d(
                                    'radius',
                                    'No active RADIUS session for {0} found.',
                                    $account->username
                                ));
                            }

                            foreach ($radaccts as $session) {
                                $this->sendDisconnectRequest($session);
                            }

                            unset($radaccts);
                        }
                    }
                }
            }

            $this->Flash->success(
                __d(
                    'radius',
                    'Related entries for {0} RADIUS accounts were processed,'
                        . ' {1} accounts were updated, and {2} accounts failed to update.',
                    Number::format($processed),
                    Number::format($modified),
                    Number::format($failed),
                )
            );
            Log::info(
                'Related entries for ' . Number::format($processed) . ' RADIUS accounts were processed, '
                    . Number::format($modified) . ' accounts were updated, and '
                    . Number::format($failed) . ' accounts failed to update.'
            );
        }
    }

    /**
     * generate data for radcheck table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array<\Radius\Model\Entity\Radcheck>
     */
    private function autoRadcheckData(Account $account): array
    {
        $radcheck = [];

        $radcheck[] = $this->fetchTable('Radius.Radcheck')
            ->findOrNewEntity([
                'username' => $account->username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $account->password,
            ]);
        if (!$account->active) {
            $radcheck[] = $this->fetchTable('Radius.Radcheck')
                ->findOrNewEntity([
                    'username' => $account->username,
                    'attribute' => 'Auth-Type',
                    'op' => ':=',
                    'value' => 'Reject',
                ]);
        }

        return $radcheck;
    }

    /**
     * generate data for radreply table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array<\Radius\Model\Entity\Radreply>
     */
    private function autoRadreplyData(Account $account): array
    {
        /** @var \App\Model\Entity\Contract $contract */
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, contain: [
            'IpNetworks',
            'Ips',
        ]);

        $radreply = [];

        foreach ($contract->ips as $ip) {
            // Skip IP addresses without RADIUS usage type
            if (!($ip->type_of_use === 00)) {
                continue;
            }

            [$address] = explode('/', $ip->ip);

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-IP-Address',
                        'op' => '=',
                        'value' => $address,
                    ]);
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-IPv6-Address',
                        'op' => '=',
                        'value' => $address,
                    ]);
            }
        }

        foreach ($contract->ip_networks as $ipNetwork) {
            // Skip IP networks without RADIUS usage type
            if (!($ipNetwork->type_of_use === 00)) {
                continue;
            }

            [$address, $mask] = explode('/', $ipNetwork->ip_network);

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-Route',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ]);
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-IPv6-Prefix',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ]);
                $radreply[] = $this->fetchTable('Radius.Radreply')
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Delegated-IPv6-Prefix',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ]);
            }
        }

        if (empty($radreply)) {
            $this->Flash->warning(
                __d(
                    'radius',
                    'The RADIUS replies for {0} could not be found automatically. Please, set it manually.',
                    $account->username
                )
                . ' ('
                . __d('radius', 'The IP addresses for the contract are probably not set correctly.')
                . ')'
            );
            Log::warning('The RADIUS replies for ' . $account->username . ' could not be found automatically.');
        }

        if (empty($radreply)) {
            // return current radusergroup records
            if (is_array($account->radreply)) {
                foreach ($account->radreply as $current_radreply) {
                    $radreply[] = $current_radreply;
                }
            }
        }

        return $radreply;
    }

    /**
     * generate data for radusergroup table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array<\Radius\Model\Entity\Radusergroup>
     */
    private function autoRadusergroupData(Account $account): array
    {
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, contain: [
            'Billings' => [
                'queryBuilder' => function (SelectQuery $q) {
                    return $q->where([
                        'Queues.name IS NOT NULL',
                        'Billings.billing_from <=' => Date::now()->addMonths(1),
                    ])
                    ->andWhere([
                        'OR' => [
                            'Billings.billing_until IS NULL',
                            'Billings.billing_until >=' => Date::now(),
                        ],
                    ])
                    ->orderBy([
                        'Billings.billing_from' => 'ASC',
                    ]);
                },
                'Services' => 'Queues',
            ],
        ]);

        $radusergroup = [];

        if (isset($contract->billings[0])) {
            // return radusergroup record with current (or the near future) queue name as groupname
            $radusergroup[] = $this->fetchTable('Radius.Radusergroup')
                ->findOrNewEntity([
                    'username' => $account->username,
                    'groupname' => $contract->billings[0]->service->queue->name,
                    'priority' => 0,
                ]);
        }

        if (empty($radusergroup)) {
            $this->Flash->warning(
                __d(
                    'radius',
                    'The RADIUS user groups for {0} could not be found automatically. Please, set it manually.',
                    $account->username
                )
                . ' ('
                . __d(
                    'radius',
                    'The billings for the contract for the current or upcoming period are probably not set correctly.'
                )
                . ')'
            );
            Log::warning('The RADIUS user groups for ' . $account->username . ' could not be found automatically.');
        }

        if (empty($radusergroup) && env('RADIUS_DEFAULT_USER_GROUP')) {
            // return radusergroup record with default user group if set in configuration
            $radusergroup[] = $this->fetchTable('Radius.Radusergroup')
                ->findOrNewEntity([
                    'username' => $account->username,
                    'groupname' => env('RADIUS_DEFAULT_USER_GROUP'),
                    'priority' => 0,
                ]);
        }

        if (empty($radusergroup)) {
            // return current radusergroup records if exists
            if (is_array($account->radusergroup)) {
                foreach ($account->radusergroup as $current_radusergroup) {
                    $radusergroup[] = $current_radusergroup;
                }
            }
        }

        return $radusergroup;
    }
}
