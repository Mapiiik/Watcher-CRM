<?php
declare(strict_types=1);

namespace Radius\Controller;

use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Mapik\RadiusClient\Client;
use Mapik\RadiusClient\Exceptions\ClientException;
use Mapik\RadiusClient\Packet;
use Mapik\RadiusClient\PacketType;
use Radius\Model\Entity\Account;

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

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['Accounts.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Accounts.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
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
        $account = $this->Accounts->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Radcheck',
                'Radreply',
                'Radusergroup',
                'Creators',
                'Modifiers',
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
    public function monitoring(?string $id = null)
    {
        $account = $this->Accounts->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Radacct' => ['sort' => ['Radacct.acctstarttime' => 'DESC']],
                'Radpostauth' => ['sort' => ['Radpostauth.authdate' => 'DESC']],
                'Creators',
                'Modifiers',
            ],
        ]);

        $details = $this->request->getQuery('show_details') == true;

        $this->set(compact('account', 'details'));
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
                    $account->radcheck = $this->autoRadcheckData($account);
                    $account->radreply = $this->autoRadreplyData($account);
                    $account->radusergroup = $this->autoRadusergroupData($account);
                }

                if ($this->Accounts->save($account)) {
                    $this->Flash->success(__d('radius', 'The RADIUS account has been saved.'));

                    return $this->redirect(['action' => 'view', $account->id]);
                }
                $this->Flash->error(__d('radius', 'The RADIUS account could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Accounts->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
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
    public function edit(?string $id = null)
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
                    $account->radcheck = $this->autoRadcheckData($account);
                }

                if ($this->Accounts->save($account)) {
                    $this->Flash->success(__d('radius', 'The RADIUS account has been saved.'));

                    return $this->redirect(['action' => 'view', $account->id]);
                }
                $this->Flash->error(__d('radius', 'The RADIUS account could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Accounts->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
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
    public function delete(?string $id = null)
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
     * Disconnect request method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects always to monitoring.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function disconnectRequest(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $account = $this->Accounts->get($id, contain: [
                'Radacct' => [
                    'conditions' => [
                        'Radacct.acctstoptime IS' => null,
                    ],
                ],
            ],
        );

        if (empty($account->radacct)) {
            $this->Flash->warning(__d(
                'radius',
                'No active RADIUS session found.'
            ));

            return $this->redirect(['action' => 'monitoring', $account->id]);
        }

        foreach ($account->radacct as $session) {
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
                    'The RADIUS session started on {0} could not be disconnected ({1}).',
                    $session->acctstarttime,
                    $e->getMessage()
                ));

                // skip further processing and go to the next record
                continue;
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
                    'The RADIUS session started on {0} has been disconnected ({1}).',
                    $session->acctstarttime,
                    $error ? $result . ' - ' . $error : $result
                ));
            } else {
                $this->Flash->error(__d(
                    'radius',
                    'The RADIUS session started on {0} could not be disconnected ({1}).',
                    $session->acctstarttime,
                    $error ? $result . ' - ' . $error : $result
                ));
            }
        }

        // wait one second for RADIUS records to update
        sleep(1);

        return $this->redirect($this->referer(['action' => 'monitoring', $account->id]));
    }

    /**
     * Update method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects always to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function updateRelatedRecords(?string $id = null)
    {
        $this->request->allowMethod(['post']);
        $account = $this->Accounts->get($id, [
            'contain' => ['Radcheck', 'Radreply', 'Radusergroup'],
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
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, [
            'contain' => ['Ips', 'IpNetworks'],
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
            }
        }

        if (empty($radreply)) {
            $this->Flash->warning(
                __d('radius', 'The RADIUS replies could not be found automatically. Please, set it manually.')
                . ' ('
                . __d('radius', 'The IP addresses for the contract are probably not set correctly.')
                . ')'
            );
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
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, [
            'contain' => [
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
                __d('radius', 'The RADIUS user groups could not be found automatically. Please, set it manually.')
                . ' ('
                . __d(
                    'radius',
                    'The billings for the contract for the current or upcoming period are probably not set correctly.'
                )
                . ')'
            );
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
