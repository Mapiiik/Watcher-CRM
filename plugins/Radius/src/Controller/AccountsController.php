<?php
declare(strict_types=1);

namespace Radius\Controller;

use App\Controller\Traits\MessageHandlerTrait;
use App\Strings;
use Radius\Model\Entity\Radacct;
use Radius\Updater\AccountsUpdater;
use Radius\Updater\RadiusRequestSender;
use RouterOS\Client;
use RouterOS\Query;

/**
 * Accounts Controller
 *
 * @property \Radius\Model\Table\AccountsTable $Accounts
 * @method \Radius\Model\Entity\Account[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class AccountsController extends AppController
{
    use MessageHandlerTrait;

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
                    // load accounts updater
                    $accountsUpdater = new AccountsUpdater();

                    // autogenerate related records
                    $account->radcheck = $accountsUpdater->autoRadcheckData($account);
                    $account->radreply = $accountsUpdater->autoRadreplyData($account);
                    $account->radusergroup = $accountsUpdater->autoRadusergroupData($account);

                    // load messages from accounts updater and generate flash messages
                    $this->handleMessages($accountsUpdater->Messages->getMessages());
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
                    // load accounts updater
                    $accountsUpdater = new AccountsUpdater();

                    // autogenerate related records
                    $account->radcheck = $accountsUpdater->autoRadcheckData($account);

                    // load messages from accounts updater and generate flash messages
                    $this->handleMessages($accountsUpdater->Messages->getMessages());
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
     * Remove MAC address method
     *
     * @param string|null $id Account id.
     * @return \Cake\Http\Response|null|void Redirects always to monitoring.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function removeMacAddress(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);
        $account = $this->Accounts->get($id, contain: [
            'Radacct' => [
                'sort' => [
                    'Radacct.acctstarttime' => 'DESC',
                ],
            ],
        ]);

        if (empty($account->radacct)) {
            $this->Flash->warning(__d(
                'radius',
                'No RADIUS session for {0} found.',
                $account->username
            ));

            return $this->redirect(['action' => 'monitoring', $account->id]);
        }

        if ($account->radacct[0] instanceof Radacct) {
            $session = $account->radacct[0];

            $result = '';

            $client = new Client([
                'host' => $session->nasipaddress,
                'user' => env('ROUTEROS_USERNAME', 'admin'),
                'pass' => env('ROUTEROS_PASSWORD', ''),
            ]);

            $query = new Query('/interface/wireless/access-list/print');
            $query
                ->where('mac-address', $session->callingstationid)
                ->equal('.proplist', '.id,interface,mac-address');

            $response = $client->query($query)->read();

            foreach ($response as $item) {
                $query = new Query('/interface/wireless/access-list/remove');
                $query->equal('.id', $item['.id']);

                $response = $client->query($query)->read();

                // check if no error message
                if (empty($response)) {
                    $result .= __d(
                        'radius',
                        'Removed MAC address entry {0} on interface {1} from router {2}.',
                        $item['mac-address'],
                        $item['interface'],
                        $session->nasipaddress
                    ) . PHP_EOL;
                }
            }

            $this->Flash->success(
                '<strong>' . __d('radius', 'Access point updated.') . '</strong><br>'
                    . ($result ? nl2br($result) : __d('radius', 'Nothing has changed.')),
                ['escape' => false]
            );
        } else {
            $this->Flash->warning(__d(
                'radius',
                'Invalid RADIUS session for {0}.',
                $account->username
            ));

            return $this->redirect(['action' => 'monitoring', $account->id]);
        }

        return $this->redirect($this->referer(['action' => 'monitoring', $account->id]));
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

        // load RADIUS Request sender
        $radiusRequestSender = new RadiusRequestSender();

        // send RADIUS disconnect requests for all open sessions
        foreach ($account->radacct as $session) {
            $radiusRequestSender->sendDisconnectRequest($session);
        }
        // load messages from RADIUS Request sender and generate flash messages
        $this->handleMessages($radiusRequestSender->Messages->getMessages());

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

        // load accounts updater
        $accountsUpdater = new AccountsUpdater();

        // update related records for account
        $accountsUpdater->updateRelatedRecords($id);

        // load messages from accounts updater and generate flash messages
        $this->handleMessages($accountsUpdater->Messages->getMessages());

        return $this->redirect($this->referer(['action' => 'view', $id]));
    }

    /**
     * Update related records for all accounts method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function updateRelatedRecordsForAllAccounts()
    {
        if ($this->getRequest()->is(['post'])) {
            // load accounts updater
            $accountsUpdater = new AccountsUpdater();

            // update related records for all accounts
            $changelog = $accountsUpdater->updateRelatedRecordsForAllAccounts($this->getRequest()->getData());

            // load messages from accounts updater and generate flash messages
            $this->handleMessages($accountsUpdater->Messages->getMessages());

            // changelog
            $this->set('changelog', $changelog);
        }
    }
}
