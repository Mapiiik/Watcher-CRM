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
