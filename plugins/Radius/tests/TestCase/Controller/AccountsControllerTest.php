<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Controller\AccountsController;

/**
 * Radius\Controller\AccountsController Test Case
 */
#[UsesClass(AccountsController::class)]
class AccountsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Queues',
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
     * login method
     *
     * @return void
     */
    protected function login(): void
    {
        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get(Configure::read('Users.table', 'Users'));

        $user = $usersTable->newEmptyEntity();
        $user->username = 'tester';
        $user->role = 'admin';
        $user->active = true;

        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
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
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
