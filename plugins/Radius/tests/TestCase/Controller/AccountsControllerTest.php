<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Controller\AccountsController;
use Radius\Updater\ChangeLog\RadcheckChange;

/**
 * Radius\Controller\AccountsController Test Case
 */
#[UsesClass(AccountsController::class)]
class AccountsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use HttpClientTrait;
    use IntegrationTestTrait;

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Contracts',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radcheck',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Radacct',
        'plugin.Radius.Radpostauth',
    ];

    /**
     * Account of the fixture
     */
    private const ACCOUNT_ID = 'ab8f2c14-6d3e-4b91-9f0a-7c25d8e41b63';

    /**
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Added under its customer and the contract, the account is filed under them without the form
     * saying so.
     *
     * The form under a customer and a contract leaves those fields out - the route already says
     * which record it is, and the controller fills them in. Posting them in the body instead, as a
     * test reaching the flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // saving an account writes its checks, replies and groups too, and the fixtures left those
        // identity columns where they started
        foreach (['Radius.Radcheck', 'Radius.Radreply', 'Radius.Radusergroup'] as $related) {
            $this->advanceIdentity($related, 'id');
        }

        $before = $this->idsIn('Radius.Accounts');
        $this->post(
            '/radius/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/accounts/add',
            [
                'username' => 'nested-account',
                'password' => 'secret-password',
                'type' => 0,
                'active' => true,
            ],
        );

        $this->assertRedirect();
        $added = $this->addedRecord('Radius.Accounts', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * The listing renders.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/radius/accounts');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/radius/accounts?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The historical connections of the account is a click away, narrowed down to
     * it rather than opening on everything ever recorded.
     *
     * The filter has to travel as a query parameter: a bare key in the URL
     * array is offered to the route, matched by nothing, and quietly dropped.
     *
     * @return void
     */
    public function testViewLinksToTheHistoricalConnectionsOfTheAccount(): void
    {
        $this->login();

        $this->get('/radius/accounts/view/' . self::ACCOUNT_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('/historical-connections?source_reference=');
    }

    /**
     * The monitoring reaches the same history, and just as narrowly.
     *
     * @return void
     */
    public function testMonitoringLinksToTheHistoricalConnectionsOfTheAccount(): void
    {
        $this->login();

        $this->get('/radius/accounts/monitoring/' . self::ACCOUNT_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('/historical-connections?source_reference=');
    }

    /**
     * The form for a new account renders.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/radius/accounts/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing account renders.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/radius/accounts/edit/' . self::ACCOUNT_ID);

        $this->assertResponseOk();
    }

    /**
     * Unticking Active draws the form again with the offer to clean up the access point, ticked in
     * advance.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::edit()
     */
    public function testEditOffersToRemoveTheMacAddressWhenDeactivating(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/edit/' . self::ACCOUNT_ID, [
            'active' => 0,
            'refresh' => 'refresh',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('id="remove-mac-address" checked="checked"');
    }

    /**
     * Unticking Active and saving is what shuts somebody out: the check that refuses them is what
     * FreeRADIUS reads, whatever the account says. Ticking it again takes that away.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::edit()
     */
    public function testAnAccountSwitchedOffIsRefusedByTheChecks(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        foreach (['Radius.Radcheck', 'Radius.Radreply', 'Radius.Radusergroup'] as $related) {
            $this->advanceIdentity($related, 'id');
        }

        $accounts = $this->fetchTable('Radius.Accounts');
        $username = $accounts->get(self::ACCOUNT_ID)->get('username');
        $refused = ['username' => $username, 'attribute' => 'Auth-Type', 'op' => ':=', 'value' => 'Reject'];

        $this->post('/radius/accounts/edit/' . self::ACCOUNT_ID, ['active' => 0]);

        $this->assertRedirect();
        $this->assertFalse($accounts->get(self::ACCOUNT_ID)->get('active'));
        $this->assertTrue($this->fetchTable('Radius.Radcheck')->exists($refused));

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/edit/' . self::ACCOUNT_ID, ['active' => 1]);

        $this->assertRedirect();
        $this->assertTrue($accounts->get(self::ACCOUNT_ID)->get('active'));
        $this->assertFalse($this->fetchTable('Radius.Radcheck')->exists($refused));
    }

    /**
     * An account that stays active has nothing to clean up, so the offer is not there.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::edit()
     */
    public function testEditDoesNotOfferToRemoveTheMacAddressOtherwise(): void
    {
        $this->login();
        $this->get('/radius/accounts/edit/' . self::ACCOUNT_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains('remove_mac_address');
    }

    /**
     * An active account is not deleted, it has to be deactivated first.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::delete()
     */
    public function testDeleteRefusesAnActiveAccount(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/radius/accounts/delete/' . self::ACCOUNT_ID);

        $this->assertRedirect();
        $this->assertFlashMessageContains('An active RADIUS account cannot be deleted. Deactivate it first.');
        $this->assertTrue($this->fetchTable('Radius.Accounts')->exists(['id' => self::ACCOUNT_ID]));
    }

    /**
     * An account switched off and never used goes, and what FreeRADIUS would read for it goes along.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::delete()
     */
    public function testDelete(): void
    {
        $accounts = $this->fetchTable('Radius.Accounts');
        $account = $accounts->get(self::ACCOUNT_ID);
        $accounts->updateAll(['active' => false], ['id' => self::ACCOUNT_ID]);
        $this->fetchTable('Radius.Radacct')->deleteAll(['1 = 1']);
        $this->fetchTable('Radius.Radpostauth')->deleteAll(['1 = 1']);
        $checks = $this->fetchTable('Radius.Radcheck');
        $this->assertTrue($checks->exists(['username' => $account->get('username')]));

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/delete/' . self::ACCOUNT_ID);

        $this->assertRedirect();
        $this->assertFalse($accounts->exists(['id' => self::ACCOUNT_ID]));
        $this->assertFalse($checks->exists(['username' => $account->get('username')]));
    }

    /**
     * An account somebody has used stays, as its sessions and its attempts to sign in say who held
     * an address and when. Either of them is enough.
     *
     * @param string $theOtherOne The record of use that is not there.
     * @return void
     * @link \Radius\Model\Table\AccountsTable::buildRules()
     */
    #[TestWith(['Radius.Radacct'])]
    #[TestWith(['Radius.Radpostauth'])]
    public function testAnAccountThatHasBeenUsedStays(string $theOtherOne): void
    {
        $accounts = $this->fetchTable('Radius.Accounts');
        $accounts->updateAll(['active' => false], ['id' => self::ACCOUNT_ID]);
        $this->fetchTable($theOtherOne)->deleteAll(['1 = 1']);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/delete/' . self::ACCOUNT_ID);

        $this->assertRedirect();
        $this->assertTrue($accounts->exists(['id' => self::ACCOUNT_ID]));
    }

    /**
     * The change log leads with the accounts the change has not reached, and why.
     *
     * The page and the e-mail sent from cron render the same element, so this is what the e-mail
     * says too.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::updateRelatedRecordsForAllAccounts()
     */
    public function testUpdateRelatedRecordsForAllAccountsReportsWhatWasNotApplied(): void
    {
        Configure::write('Agent.url', 'https://agent.example.com');
        Configure::write('Agent.token', 'agent-token');
        $this->fetchTable('Radius.Radacct')->updateAll(['acctstoptime' => null], ['1 = 1']);
        $this->mockClientPost(
            'https://agent.example.com/api/radius/disconnect',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'success' => false,
                'result' => 'Timeout after 3 attempts',
            ])),
        );

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/update-related-records-for-all-accounts', [
            'state' => 'active',
            'radcheck' => '1',
            'radreply' => '0',
            'radusergroup' => '0',
            'reconnect_modified_accounts' => '1',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('The changes could not be applied to these accounts.');
        $this->assertResponseContains('Timeout after 3 attempts');
    }

    /**
     * Unticking Active ends what is running. The reject written to the account stops the next
     * sign-in only, and a customer already connected would go on for days.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::edit()
     */
    public function testDeactivatingEndsTheOpenSessions(): void
    {
        Configure::write('Agent.url', 'https://agent.example.com');
        Configure::write('Agent.token', 'agent-token');
        $this->fetchTable('Radius.Radacct')->updateAll(['acctstoptime' => null], ['1 = 1']);
        $this->mockClientPost(
            'https://agent.example.com/api/radius/disconnect',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'success' => true,
                'result' => 'Disconnect-ACK',
            ])),
        );

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/edit/' . self::ACCOUNT_ID, ['active' => 0]);

        $this->assertRedirect();
        $this->assertStringContainsString('has been disconnected', $this->messagesLeft());
    }

    /**
     * A session whose row kept the stop time of an earlier one is running all the same, and is
     * ended with the account.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::endOpenSessions()
     */
    public function testDeactivatingEndsASessionWhoseStopTimeIsOlderThanItsStart(): void
    {
        Configure::write('Agent.url', 'https://agent.example.com');
        Configure::write('Agent.token', 'agent-token');
        $this->fetchTable('Radius.Radacct')->updateAll(
            ['acctstarttime' => '2026-04-10 00:00:00', 'acctstoptime' => '2026-03-01 00:00:00'],
            ['1 = 1'],
        );
        $this->mockClientPost(
            'https://agent.example.com/api/radius/disconnect',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'success' => true,
                'result' => 'Disconnect-ACK',
            ])),
        );

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/edit/' . self::ACCOUNT_ID, ['active' => 0]);

        $this->assertRedirect();
        $this->assertStringContainsString('has been disconnected', $this->messagesLeft());
    }

    /**
     * Disconnecting by hand reaches such a session too.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::disconnectRequest()
     */
    public function testDisconnectingReachesASessionWhoseStopTimeIsOlderThanItsStart(): void
    {
        Configure::write('Agent.url', 'https://agent.example.com');
        Configure::write('Agent.token', 'agent-token');
        $this->fetchTable('Radius.Radacct')->updateAll(
            ['acctstarttime' => '2026-04-10 00:00:00', 'acctstoptime' => '2026-03-01 00:00:00'],
            ['1 = 1'],
        );
        $this->mockClientPost(
            'https://agent.example.com/api/radius/disconnect',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'success' => true,
                'result' => 'Disconnect-ACK',
            ])),
        );

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/disconnect-request/' . self::ACCOUNT_ID);

        $this->assertRedirect();
        $this->assertStringContainsString('has been disconnected', $this->messagesLeft());
    }

    /**
     * An account that stays active keeps its session, saving it disconnects nothing.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::edit()
     */
    public function testSavingAnActiveAccountDisconnectsNothing(): void
    {
        Configure::write('Agent.url', 'https://agent.example.com');
        Configure::write('Agent.token', 'agent-token');
        $this->fetchTable('Radius.Radacct')->updateAll(['acctstoptime' => null], ['1 = 1']);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/edit/' . self::ACCOUNT_ID, ['active' => 1]);

        $this->assertRedirect();
        $this->assertStringNotContainsString('disconnected', $this->messagesLeft());
    }

    /**
     * The RADIUS database knows nothing of what happens to a contract, and an account whose
     * contract is gone stops the nightly update of the accounts where it stands.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::buildRules()
     */
    public function testAContractWithAnAccountIsNotDeleted(): void
    {
        $contractId = '7f76dc3f-a11b-4109-958b-4b0382545a66';

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contracts/delete/' . $contractId);

        $this->assertTrue($this->fetchTable('Contracts')->exists(['id' => $contractId]));
        $this->assertStringContainsString('still has a RADIUS account', $this->messagesLeft());
    }

    /**
     * The overview of what changed, which also goes out by e-mail, says that a password changed
     * and never what it is. The audit log the same.
     *
     * @return void
     * @link \Radius\Updater\ChangeLog\RadcheckChange::changedToRead()
     */
    public function testAPasswordIsNotWrittenOutWhereChangesAreRead(): void
    {
        $check = $this->fetchTable('Radius.Radcheck')->newEmptyEntity();
        $check->patch(['attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => 'The password'], ['guard' => false]);
        $other = $this->fetchTable('Radius.Radcheck')->newEmptyEntity();
        $other->patch(['attribute' => 'Auth-Type', 'op' => ':=', 'value' => 'Reject'], ['guard' => false]);

        $rows = (new RadcheckChange([$check, $other], []))->originalToRead();

        $this->assertSame('****', $rows[0]['value']);
        $this->assertSame('Reject', $rows[1]['value']);
        $this->assertSame(['password'], $this->fetchTable('Radius.Accounts')->getBehavior('AuditLog')->getConfig('sensitive'));
    }

    /**
     * Only a password is hidden in the audit log. Auth-Type and the rest say what happened to the
     * account, so they have to stay readable, when a check is written and when it is deleted.
     *
     * @return void
     * @link \Radius\Model\Table\RadcheckTable::beforeSave()
     */
    public function testTheAuditLogHidesAPasswordAndNothingElse(): void
    {
        $checks = $this->fetchTable('Radius.Radcheck');
        $check = fn(int $id, string $attribute, string $value) => $checks->saveOrFail($checks->newEntity(
            ['id' => $id, 'username' => 'audit-test', 'attribute' => $attribute, 'op' => ':=', 'value' => $value],
            ['validate' => false, 'accessibleFields' => ['*' => true]],
        ), ['checkRules' => false]);
        $logs = $this->getTableLocator()->get('AuditStash.AuditLogs');
        $logs->deleteAll(['1 = 1']);
        $logged = function (string $column) use ($logs): ?string {
            $data = $logs->find()->firstOrFail()->get($column);
            $logs->deleteAll(['1 = 1']);

            return (is_string($data) ? json_decode($data, true) : $data)['value'] ?? null;
        };

        $password = $check(9001, 'Cleartext-Password', 'The password');
        $this->assertSame('****', $logged('changed'));
        $reject = $check(9002, 'Auth-Type', 'Reject');
        $this->assertSame('Reject', $logged('changed'));

        $checks->deleteOrFail($password);
        $this->assertSame('****', $logged('original'));
        $checks->deleteOrFail($reject);
        $this->assertSame('Reject', $logged('original'));
    }

    /**
     * What was left for the next page to say.
     *
     * @return string
     */
    private function messagesLeft(): string
    {
        return (string)json_encode($_SESSION['Flash'] ?? []);
    }
}
