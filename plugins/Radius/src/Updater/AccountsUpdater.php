<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Messages\Messages;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Exception;
use Radius\Model\Entity\Account;
use Radius\Model\Table\AccountsTable;
use Radius\Model\Table\RadcheckTable;
use Radius\Model\Table\RadreplyTable;
use Radius\Model\Table\RadusergroupTable;
use Radius\Updater\ChangeLog\ChangeLog;

/**
 * Accounts Updater
 */
class AccountsUpdater
{
    use LocatorAwareTrait;

    /**
     * Messages
     */
    public Messages $Messages;

    /**
     * Accounts Table
     */
    protected AccountsTable $Accounts;

    /**
     * Radcheck Table
     */
    protected RadcheckTable $Radcheck;

    /**
     * Radreply Table
     */
    protected RadreplyTable $Radreply;

    /**
     * Radusergroup Table
     */
    protected RadusergroupTable $Radusergroup;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Messages = new Messages();

        /** @phpstan-ignore-next-line */
        $this->Accounts = $this->fetchTable('Radius.Accounts');
        /** @phpstan-ignore-next-line */
        $this->Radcheck = $this->fetchTable('Radius.Radcheck');
        /** @phpstan-ignore-next-line */
        $this->Radreply = $this->fetchTable('Radius.Radreply');
        /** @phpstan-ignore-next-line */
        $this->Radusergroup = $this->fetchTable('Radius.Radusergroup');
    }

    /**
     * Update related records method
     *
     * @param string|null $id Account id.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function updateRelatedRecords(?string $id = null): bool
    {
        // load account
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
            $this->Messages->success(__d('radius', 'The RADIUS account has been updated.'));

            return true;
        } else {
            $this->Messages->error(__d('radius', 'The RADIUS account could not be updated. Please, try again.'));

            return false;
        }
    }

    /**
     * Update related records for all accounts method
     *
     * @return \Radius\Updater\ChangeLog\ChangeLog ChangeLog with changed accounts and details of what has changed.
     */
    public function updateRelatedRecordsForAllAccounts(?array $options): ChangeLog
    {
        // load options
        $options = $options + [
            'state' => 'active',
            'radcheck' => false,
            'radreply' => false,
            'radusergrioup' => false,
            'reconnect_modified_accounts' => false,
            'send_change_log_by_email' => false,
        ] + $options;

        // load accounts and related records
        $accountsQuery = $this->Accounts->find('all', contain: [
            'Radcheck',
            'Radreply',
            'Radusergroup',
        ]);

        // initializes the change log
        $changelog = new ChangeLog();

        // stop processing if there is nothing to do
        if (!(($options['radcheck'] == true) | ($options['radreply'] == true) | ($options['radusergroup'] == true))) {
            $this->Messages->warning(__d('radius', 'Nothing has been selected for update.'));

            return $changelog;
        }

        // filter accounts by required state
        switch ($options['state']) {
            case 'active':
                $accountsQuery->where(['active' => true]);
                break;
            case 'inactive':
                $accountsQuery->where(['active' => false]);
                break;
        }

        // initialization of counters
        $processed = 0;
        $modified = 0;
        $failed = 0;

        /** @var iterable<\Radius\Model\Entity\Account> $accounts */
        $accounts = $accountsQuery->all();

        foreach ($accounts as $account) {
            // autogenerate related records
            if ($options['radcheck'] == true) {
                $this->autoDataHandler('radcheck', 'autoRadcheckData', $account, $changelog);
            }
            if ($options['radreply'] == true) {
                $this->autoDataHandler('radreply', 'autoRadreplyData', $account, $changelog);
            }
            if ($options['radusergroup'] == true) {
                $this->autoDataHandler('radusergroup', 'autoRadusergroupData', $account, $changelog);
            }

            $processed++;
            if ($changelog->hasChange($account->username)) {
                // save modified data
                if ($this->Accounts->save($account) === false) {
                    $failed++;

                    $this->Messages->error(
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
                    if ($options['reconnect_modified_accounts'] == true) {
                        $radaccts = $this->Accounts->Radacct
                            ->find()
                            ->where([
                                'Radacct.username' => $account->username,
                                'Radacct.acctstoptime IS' => null,
                            ])
                            ->all()
                            ->toArray();

                        if (empty($radaccts)) {
                            $this->Messages->warning(__d(
                                'radius',
                                'No active RADIUS session for {0} found.',
                                $account->username
                            ));
                        } else {
                            // load RADIUS request sender if required
                            if (!isset($radiusRequestSender)) {
                                $radiusRequestSender = new RadiusRequestSender();
                            }

                            // send RADIUS disconnect requests for all open sessions
                            foreach ($radaccts as $session) {
                                $radiusRequestSender->sendDisconnectRequest($session);
                            }
                        }

                        unset($radaccts);
                    }
                }
            }
        }

        $this->Messages->success(
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

        // send change log by email
        if ($options['send_change_log_by_email'] == true) {
            $mailer = new Mailer('default');

            foreach (explode(' ', (string)env('REPORT_EMAILS')) as $email) {
                $mailer->addTo($email);
            }

            $mailer->setSubject(
                __d('radius', 'Automatic RADIUS account changes') . ' - ' . Date::now()->i18nFormat('yyyy-MM-dd')
            );
            $mailer->setEmailFormat('html');

            $mailer->viewBuilder()
                ->setLayout('default')
                ->setTemplate('Radius.UpdateRelatedRecordsSummary');

            $mailer->setViewVars([
                'title' => __d('radius', 'These automatic RADIUS account changes have just taken place.'),
                'changelog' => $changelog,
            ]);

            try {
                $mailer->deliver();
                Log::write('debug', 'Automatic RADIUS account changes have been reported.');
                $this->Messages->info(__d('radius', 'Automatic RADIUS account changes have been reported.'));
            } catch (Exception $e) {
                Log::write(
                    'error',
                    'Automatic RADIUS account changes cannot be reported. (' . $e->getMessage() . ')'
                );
                $this->Messages->error(__d('radius', 'Automatic RADIUS account changes cannot be reported.'));
            }
        }

        return $changelog;
    }

    /**
     * Handles automatic data generation for related records.
     *
     * @param string $relatedData The name of the related data.
     * @param string $autoDataMethod The name of the method to generate the data.
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @param \Radius\Updater\ChangeLog\ChangeLog $changelog Reference to the ChangeLog.
     */
    private function autoDataHandler(
        string $relatedData,
        string $autoDataMethod,
        Account &$account,
        ChangeLog &$changelog,
    ): void {
        $data = $this->$autoDataMethod($account);
        sort($data);
        sort($account->$relatedData);
        if ($account->$relatedData != $data) {
            // write changes to the change log
            $changelog->addChangeForRelatedData(
                $account,
                $relatedData,
                original: $account->$relatedData,
                changed: $data,
            );

            $account->$relatedData = $data;
        }
    }

    /**
     * generate data for radcheck table for customer
     *
     * @param \Radius\Model\Entity\Account $account RADIUS account entity
     * @return array<\Radius\Model\Entity\Radcheck>
     */
    public function autoRadcheckData(Account $account): array
    {
        $radcheck = [];

        $radcheck[] = $this->Radcheck
            ->findOrNewEntity([
                'username' => $account->username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $account->password,
            ]);
        if (!$account->active) {
            $radcheck[] = $this->Radcheck
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
    public function autoRadreplyData(Account $account): array
    {
        /** @var \App\Model\Entity\Contract $contract */
        $contract = $this->fetchTable('Contracts')->get($account->contract_id, contain: [
            'IpAddresses',
            'IpNetworks',
        ]);

        $radreply = [];

        foreach ($contract->ip_addresses as $ipAddress) {
            // Skip IP addresses without RADIUS usage type
            if (!($ipAddress->type_of_use === 00)) {
                continue;
            }

            [$address] = explode('/', $ipAddress->ip_address);

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $radreply[] = $this->Radreply
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-IP-Address',
                        'op' => '=',
                        'value' => $address,
                    ]);
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $radreply[] = $this->Radreply
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
                $radreply[] = $this->Radreply
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-Route',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ]);
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $radreply[] = $this->Radreply
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Framed-IPv6-Prefix',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ]);
                $radreply[] = $this->Radreply
                    ->findOrNewEntity([
                        'username' => $account->username,
                        'attribute' => 'Delegated-IPv6-Prefix',
                        'op' => '=',
                        'value' => $address . '/' . $mask,
                    ]);
            }
        }

        if (empty($radreply)) {
            $this->Messages->warning(
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
    public function autoRadusergroupData(Account $account): array
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
            $radusergroup[] = $this->Radusergroup
                ->findOrNewEntity([
                    'username' => $account->username,
                    'groupname' => $contract->billings[0]->service->queue->name,
                    'priority' => 0,
                ]);
        }

        if (empty($radusergroup)) {
            $this->Messages->warning(
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
            $radusergroup[] = $this->Radusergroup
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
