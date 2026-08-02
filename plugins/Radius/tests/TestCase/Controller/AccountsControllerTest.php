<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
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
    use ControllerTestTrait;
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
     * The delete action runs and redirects. Whether the account really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \Radius\Controller\AccountsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/accounts/delete/' . self::ACCOUNT_ID);

        $this->assertRedirect();
    }
}
