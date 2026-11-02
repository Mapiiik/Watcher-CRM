<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Updater;

use App\Model\Table\BillingsTable;
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
