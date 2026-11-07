<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Updater;

use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use App\Model\Table\BillingsTable;
use App\Model\Table\IpAddressesTable;
use App\Model\Table\IpNetworksTable;
use App\Model\Table\ServiceOverridesTable;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Http\Message\RequestInterface;
use Radius\Model\Entity\Account;
use Radius\Model\Entity\Radcheck;
use Radius\Model\Entity\Radreply;
use Radius\Model\Entity\Radusergroup;
use Radius\Model\Table\RadacctTable;
use Radius\Test\Traits\TariffsTestTrait;
use Radius\Updater\AccountsUpdater;
use Radius\Updater\ChangeLog\Change;
use Radius\Updater\ChangeLog\ChangeLog;
use Radius\Updater\DisconnectRequestResult;
use Radius\Updater\ExpectedUserGroups;
use Radius\Updater\RadiusRequestSender;

/**
 * Radius\Updater\AccountsUpdater Test Case
 */
#[UsesClass(AccountsUpdater::class)]
#[UsesClass(ExpectedUserGroups::class)]
#[UsesClass(RadiusRequestSender::class)]
#[UsesClass(DisconnectRequestResult::class)]
#[UsesClass(ChangeLog::class)]
#[UsesClass(Change::class)]
class AccountsUpdaterTest extends TestCase
{
    use HttpClientTrait;
    use TariffsTestTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Where the agent listens.
     *
     * @var string
     */
    private const AGENT = 'https://agent.example.com';

    /**
     * The account of the fixtures, and of their checks and sessions.
     *
     * @var string
     */
    private const USERNAME = 'subscriber';

    /**
     * That account.
     *
     * @var string
     */
    private const ACCOUNT_ID = 'ab8f2c14-6d3e-4b91-9f0a-7c25d8e41b63';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.ServiceOverrides',
        'app.IpAddresses',
        'app.IpNetworks',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radcheck',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Radacct',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Radius.defaultUserGroup', '');
        Configure::write('Agent.url', self::AGENT);
        Configure::write('Agent.token', 'agent-token');

        $this->fetchTable(BillingsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(ServiceOverridesTable::class)->deleteAll(['1 = 1']);

        // the fixtures name their account with a paragraph, which makes for unreadable failures
        $this->fetchTable('Radius.Accounts')->updateAll(['username' => self::USERNAME], ['1 = 1']);
        $this->fetchTable('Radius.Radcheck')->updateAll(['username' => self::USERNAME], ['1 = 1']);
        $this->fetchTable('Radius.Radacct')->updateAll(
            ['username' => self::USERNAME, 'acctsessionid' => 'live'],
            ['1 = 1'],
        );
    }

    /**
     * A changed account whose session stays up is reported, with the access server and what came
     * back, as it runs on what it had until it connects again.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecordsForAllAccounts()
     */
    public function testAnAccountWhoseSessionStaysUpIsReported(): void
    {
        $this->openSession();
        $this->agentAnswers('live', ['success' => false, 'result' => 'Timeout after 3 attempts']);

        $failures = $this->failures($this->reconnect());

        $this->assertCount(1, $failures);
        $this->assertStringContainsString('10.10.10.1', $failures[0]);
        $this->assertStringContainsString('Timeout after 3 attempts', $failures[0]);
    }

    /**
     * One dropped session is enough. The others an account has open are mostly ones the access
     * server never closed, which it refuses as sessions it does not know.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecordsForAllAccounts()
     */
    public function testOneDroppedSessionIsEnough(): void
    {
        $this->openSession();
        $this->openSession('dead');
        $this->agentAnswers('live', ['success' => true, 'result' => 'Disconnect-ACK']);
        $this->agentAnswers('dead', ['success' => false, 'result' => 'Disconnect-NAK', 'error_causes' => [503]]);

        $changelog = $this->reconnect();

        $this->assertCount(1, $changelog->getChanges());
        $this->assertSame([], $changelog->getFailedChanges());
    }

    /**
     * Without an agent nothing gets dropped, which is a failure like any other - the run was told
     * to reconnect.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecordsForAllAccounts()
     */
    public function testWithoutTheAgentTheAccountIsReported(): void
    {
        Configure::write('Agent.url', '');
        $this->openSession();

        $failures = $this->failures($this->reconnect());

        $this->assertCount(1, $failures);
        $this->assertStringContainsString('Watcher Agent is not configured.', $failures[0]);
    }

    /**
     * An account being changed is expected to be connected, so one with no open session is
     * reported too, without asking the agent anything.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecordsForAllAccounts()
     */
    public function testAnAccountWithoutAnOpenSessionIsReported(): void
    {
        $sent = $this->agentAnswers('live', ['success' => true, 'result' => 'Disconnect-ACK']);

        $failures = $this->failures($this->reconnect());

        $this->assertSame(['No active RADIUS session found.'], $failures);
        $this->assertCount(0, $sent);
    }

    /**
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadusergroupData()
     */
    public function testTheGroupIsTheOneTheTariffNames(): void
    {
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        $this->overridden(self::CONTRACT_ID, $this->tariff('fast'), Date::today(), Date::today());

        $groups = (new AccountsUpdater())->autoRadusergroupData($this->account());

        $this->assertSame(['fast'], array_map(fn(Radusergroup $group): string => $group->groupname, $groups));
    }

    /**
     * With no tariff and no default group, the account keeps the groups it has.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadusergroupData()
     */
    public function testWithoutATariffTheCurrentGroupsAreKept(): void
    {
        $account = $this->account();
        $account->radusergroup = [new Radusergroup(['username' => 'test', 'groupname' => 'kept'])];

        $groups = (new AccountsUpdater())->autoRadusergroupData($account);

        $this->assertSame(['kept'], array_map(fn(Radusergroup $group): string => $group->groupname, $groups));
    }

    /**
     * The nightly update reaches a session whose row kept the stop time of an earlier one.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecordsForAllAccounts()
     */
    public function testASessionWhoseStopTimeIsOlderThanItsStartIsReached(): void
    {
        $this->fetchTable(RadacctTable::class)->updateAll(
            ['acctstarttime' => '2026-04-10 00:00:00', 'acctstoptime' => '2026-03-01 00:00:00'],
            ['acctsessionid' => 'live'],
        );
        $sent = $this->agentAnswers('live', ['success' => true, 'result' => 'Disconnect-ACK']);

        $this->reconnect();

        $this->assertCount(1, $sent);
    }

    /**
     * An account that may connect is checked by its password and nothing turns it away.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadcheckData()
     */
    public function testAnActiveAccountIsCheckedByItsPassword(): void
    {
        $account = new Account(['username' => 'test', 'password' => 'the password', 'active' => true]);

        $this->assertSame(
            [['Cleartext-Password', ':=', 'the password']],
            $this->rows((new AccountsUpdater())->autoRadcheckData($account)),
        );
    }

    /**
     * An account switched off keeps its password and is turned away whatever it types.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadcheckData()
     */
    public function testAnAccountSwitchedOffIsRejected(): void
    {
        $account = new Account(['username' => 'test', 'password' => 'the password', 'active' => false]);

        $this->assertSame(
            [['Cleartext-Password', ':=', 'the password'], ['Auth-Type', ':=', 'Reject']],
            $this->rows((new AccountsUpdater())->autoRadcheckData($account)),
        );
    }

    /**
     * What the nightly update writes is what FreeRADIUS reads: switching an account off reaches the
     * table of checks, and switching it back on takes the rejection away again.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecordsForAllAccounts()
     */
    public function testTheNightlyUpdateWritesTheRejectionAndTakesItAway(): void
    {
        $accounts = $this->fetchTable('Radius.Accounts');
        $checks = $this->fetchTable('Radius.Radcheck');
        $rejections = ['username' => self::USERNAME, 'attribute' => 'Auth-Type', 'value' => 'Reject'];

        $accounts->updateAll(['active' => false], ['1 = 1']);
        (new AccountsUpdater())->updateRelatedRecordsForAllAccounts(['state' => 'inactive', 'radcheck' => true]);
        $this->assertSame(1, $checks->find()->where($rejections)->count());

        $accounts->updateAll(['active' => true], ['1 = 1']);
        (new AccountsUpdater())->updateRelatedRecordsForAllAccounts(['radcheck' => true]);
        $this->assertSame(0, $checks->find()->where($rejections)->count());
    }

    /**
     * The replies carry what the contract holds for RADIUS, whatever is there for other uses stays
     * out. Each kind of address has an attribute of its own, and an IPv6 network two.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadreplyData()
     */
    public function testTheRepliesAreTheRadiusAddressesOfTheContract(): void
    {
        foreach (['192.0.2.7', '2001:db8::7'] as $address) {
            $this->holdAddress($address);
        }
        foreach (['198.51.100.0/29', '2001:db8:1::/64'] as $network) {
            $this->holdNetwork($network);
        }

        $replies = (new AccountsUpdater())->autoRadreplyData($this->account());

        $this->assertEqualsCanonicalizing([
            ['Framed-IP-Address', '=', '192.0.2.7'],
            ['Framed-IPv6-Address', '=', '2001:db8::7'],
            ['Framed-Route', '=', '198.51.100.0/29'],
            ['Framed-IPv6-Prefix', '=', '2001:db8:1::/64'],
            ['Delegated-IPv6-Prefix', '=', '2001:db8:1::/64'],
        ], $this->rows($replies));
    }

    /**
     * A contract with no address for RADIUS leaves the replies as they are.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::autoRadreplyData()
     */
    public function testWithoutAnAddressTheCurrentRepliesAreKept(): void
    {
        $account = $this->account();
        $account->radreply = [new Radreply([
            'username' => 'test',
            'attribute' => 'Framed-IP-Address',
            'op' => '=',
            'value' => '192.0.2.200',
        ])];

        $replies = (new AccountsUpdater())->autoRadreplyData($account);

        $this->assertSame([['Framed-IP-Address', '=', '192.0.2.200']], $this->rows($replies));
    }

    /**
     * New replies reach a running session only by its signing in again, so updating the account
     * disconnects it, and the operator is told.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecords()
     */
    public function testUpdatingAnAccountWhoseRepliesChangedDisconnectsIt(): void
    {
        $this->clearRepliesAndGroups();
        $this->openSession();
        $sent = $this->agentAnswers('live', ['success' => true, 'result' => 'Disconnect-ACK']);
        $this->holdAddress('192.0.2.7');

        $updater = new AccountsUpdater();
        $this->assertTrue($updater->updateRelatedRecords(self::ACCOUNT_ID));

        $this->assertCount(1, $sent);
        $this->assertStringContainsString('has been disconnected', $this->said($updater));
    }

    /**
     * New groups are told at the sign-in as well.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecords()
     */
    public function testUpdatingAnAccountWhoseGroupChangedDisconnectsIt(): void
    {
        $this->clearRepliesAndGroups();
        $this->billed(self::CONTRACT_ID, self::CUSTOMER_ID, $this->tariff('slow'), Date::today()->subYears(1));
        (new AccountsUpdater())->updateRelatedRecords(self::ACCOUNT_ID);

        $this->overridden(self::CONTRACT_ID, $this->tariff('fast'), Date::today(), Date::today());
        $this->openSession();
        $sent = $this->agentAnswers('live', ['success' => true, 'result' => 'Disconnect-ACK']);

        (new AccountsUpdater())->updateRelatedRecords(self::ACCOUNT_ID);

        $this->assertCount(1, $sent);
    }

    /**
     * An update that finds nothing new leaves a running session alone, and so does a new password,
     * which is asked for only at the next sign-in.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecords()
     */
    public function testUpdatingAnAccountOtherwiseLeavesItsSessionAlone(): void
    {
        $this->clearRepliesAndGroups();
        $this->holdAddress('192.0.2.7');
        (new AccountsUpdater())->updateRelatedRecords(self::ACCOUNT_ID);

        $this->openSession();
        $sent = $this->agentAnswers('live', ['success' => true, 'result' => 'Disconnect-ACK']);

        (new AccountsUpdater())->updateRelatedRecords(self::ACCOUNT_ID);
        $this->assertCount(0, $sent);

        $this->fetchTable('Radius.Accounts')->updateAll(['password' => 'another'], ['id' => self::ACCOUNT_ID]);
        (new AccountsUpdater())->updateRelatedRecords(self::ACCOUNT_ID);
        $this->assertCount(0, $sent);
    }

    /**
     * With nothing running there is nobody to disconnect, and the update says so.
     *
     * @return void
     * @link \Radius\Updater\AccountsUpdater::updateRelatedRecords()
     */
    public function testUpdatingAnAccountWithNothingRunningSaysSo(): void
    {
        $this->clearRepliesAndGroups();
        $this->holdAddress('192.0.2.7');

        $updater = new AccountsUpdater();
        $this->assertTrue($updater->updateRelatedRecords(self::ACCOUNT_ID));

        $this->assertStringContainsString('No active RADIUS session', $this->said($updater));
    }

    /**
     * Take away the replies and groups of the fixtures, which set their ids without moving the
     * sequences on, so that an update can write its own.
     *
     * @return void
     */
    private function clearRepliesAndGroups(): void
    {
        $this->fetchTable('Radius.Radreply')->deleteAll(['1 = 1']);
        $this->fetchTable('Radius.Radusergroup')->deleteAll(['1 = 1']);
    }

    /**
     * Everything the updater has to say, in one text.
     *
     * @param \Radius\Updater\AccountsUpdater $updater The updater.
     * @return string
     */
    private function said(AccountsUpdater $updater): string
    {
        return (string)json_encode($updater->Messages->getMessages());
    }

    /**
     * Give the contract an address for RADIUS.
     *
     * @param string $address The address.
     * @return void
     */
    private function holdAddress(string $address): void
    {
        $table = $this->fetchTable(IpAddressesTable::class);
        $table->saveOrFail($table->newEntity([
            'ip_address' => $address,
            'type_of_use' => IpAddressTypeOfUse::CustomerRADIUS,
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
        ], ['validate' => false, 'accessibleFields' => ['*' => true]]), ['checkRules' => false]);
    }

    /**
     * Give the contract a network for RADIUS.
     *
     * @param string $network The network.
     * @return void
     */
    private function holdNetwork(string $network): void
    {
        $table = $this->fetchTable(IpNetworksTable::class);
        $table->saveOrFail($table->newEntity([
            'ip_network' => $network,
            'type_of_use' => IpNetworkTypeOfUse::CustomerRADIUS,
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
        ], ['validate' => false, 'accessibleFields' => ['*' => true]]), ['checkRules' => false]);
    }

    /**
     * What checks or replies say, for comparing.
     *
     * @param array<\Radius\Model\Entity\Radcheck|\Radius\Model\Entity\Radreply> $rows The rows.
     * @return list<array{string, string, string}>
     */
    private function rows(array $rows): array
    {
        return array_values(array_map(
            fn(Radcheck|Radreply $row): array => [$row->attribute, $row->op, $row->value],
            $rows,
        ));
    }

    /**
     * @return \Radius\Model\Entity\Account
     */
    private function account(): Account
    {
        return new Account(['username' => 'test', 'contract_id' => self::CONTRACT_ID, 'radusergroup' => []]);
    }

    /**
     * Change the checks of every account and drop their sessions.
     *
     * @return \Radius\Updater\ChangeLog\ChangeLog
     */
    private function reconnect(): ChangeLog
    {
        return (new AccountsUpdater())->updateRelatedRecordsForAllAccounts([
            'radcheck' => true,
            'reconnect_modified_accounts' => true,
        ]);
    }

    /**
     * Leave a session of the account open. The fixture one is reopened, any other is added.
     *
     * @param string $sessionId The session.
     * @return void
     */
    private function openSession(string $sessionId = 'live'): void
    {
        $sessions = $this->fetchTable(RadacctTable::class);

        if ($sessionId === 'live') {
            $sessions->updateAll(['acctstoptime' => null], ['acctsessionid' => 'live']);

            return;
        }

        // the fixture sets its id without moving the sequence on
        $sessions->insertQuery()
            ->insert(['radacctid', 'acctsessionid', 'acctuniqueid', 'username', 'nasipaddress', 'acctstarttime'])
            ->values([
                'radacctid' => 2,
                'acctsessionid' => $sessionId,
                'acctuniqueid' => $sessionId,
                'username' => self::USERNAME,
                'nasipaddress' => '10.10.10.2',
                'acctstarttime' => '2021-05-26 14:08:56',
            ])
            ->execute();
    }

    /**
     * Have the agent answer a disconnect of one session, writing down what it was asked.
     *
     * @param string $sessionId The session the answer is for.
     * @param array<string, mixed> $body What the agent says.
     * @return \ArrayObject<int, mixed>
     */
    private function agentAnswers(string $sessionId, array $body): ArrayObject
    {
        /** @var \ArrayObject<int, mixed> $sent */
        $sent = new ArrayObject();

        $this->mockClientPost(
            self::AGENT . '/api/radius/disconnect',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode($body)),
            ['match' => function (RequestInterface $request) use ($sessionId, $sent): bool {
                $data = json_decode((string)$request->getBody(), true);
                if (!is_array($data) || ($data['acct_session_id'] ?? null) !== $sessionId) {
                    return false;
                }
                $sent[] = $data;

                return true;
            }],
        );

        return $sent;
    }

    /**
     * @param \Radius\Updater\ChangeLog\ChangeLog $changelog What the run did.
     * @return list<string>
     */
    private function failures(ChangeLog $changelog): array
    {
        return array_merge(...array_values(array_map(
            fn(Change $change): array => $change->getFailures(),
            $changelog->getFailedChanges(),
        )));
    }
}
