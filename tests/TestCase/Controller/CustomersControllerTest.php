<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CustomersController;
use App\Model\Enum\CustomerDealer;
use App\Model\Enum\CustomerInvoiceDeliveryType;
use App\Test\Traits\ControllerTestTrait;
use Cake\Database\Connection;
use Cake\Database\Log\LoggedQuery;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;

/**
 * App\Controller\CustomersController Test Case
 */
#[UsesClass(CustomersController::class)]
class CustomersControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer owning the contract from the Contracts fixture.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Accounting profile from the AccountingProfiles fixture. Every customer belongs to one - the
     * column is not nullable - so a customer written here has to name it.
     *
     * @var string
     */
    private const ACCOUNTING_PROFILE_ID = 'ab05963c-1531-4677-a9ee-80cecde25124';

    /**
     * Contract from the Contracts fixture.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Verification code of the contract from the Contracts fixture, rendered in the column
     * the obligation column has to precede.
     *
     * @var string
     */
    private const VERIFICATION_CODE = 'Lorem ipsum dolor sit amet';

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
        'app.Contracts',
        'app.ContractVersions',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.Emails',
        'app.Labels',
        'app.CustomerLabels',
        'app.Logins',
        'app.Phones',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.DealerCommissions',
    ];

    /**
     * Adds a contract version with the given obligation date to the fixture contract.
     *
     * @param string $obligationUntil Obligation date.
     * @return void
     */
    private function addContractVersion(string $obligationUntil): void
    {
        $contractVersionsTable = $this->getTableLocator()->get('ContractVersions');
        $contractVersion = $contractVersionsTable->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => '2023-01-01',
            'obligation_until' => $obligationUntil,
            'obligations_settled' => false,
            'number_of_amendments' => 0,
        ]);
        $contractVersionsTable->saveOrFail($contractVersion);
    }

    /**
     * Asserts the obligation cell of the fixture contract, anchored to the verification code
     * cell that has to follow it.
     *
     * @param string $style Expected style attribute of the cell.
     * @param string $obligationUntil Expected content of the cell.
     * @return void
     */
    private function assertObligationCell(string $style, string $obligationUntil): void
    {
        $this->assertResponseRegExp(
            '~<td style="' . preg_quote($style, '~') . '">' . preg_quote($obligationUntil, '~') . '</td>\s*'
            . '<td>' . preg_quote(self::VERIFICATION_CODE, '~') . '</td>~',
        );
    }

    /**
     * Test index method
     *
     * The advanced search carries its parameters in the condition expression rather than binding
     * them on the query, so that they survive the derived table the eager loader builds for the
     * contained associations. Without that the contracts fetch runs with unbound placeholders.
     *
     * @return void
     * @link \App\Controller\CustomersController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/customers?advanced_search=1&search=Lorem');

        $this->assertResponseOk();

        /** @var iterable<\App\Model\Entity\Customer> $customers */
        $customers = $this->viewVariable('customers');
        $found = [];
        foreach ($customers as $customer) {
            $found[] = $customer->id;
            $this->assertNotNull($customer->contracts);
        }

        $this->assertContains(self::CUSTOMER_ID, $found);
    }

    /**
     * The advanced search is answered no more than twice per page - once for the listing and once
     * for the count that pages it.
     *
     * It builds a text document out of five aggregated tables and can be answered by no index, so
     * it costs roughly the whole database each time it is run: about 180 ms over 7500 customers.
     * The `subquery` strategy CakePHP 5.4 made the default for hasMany filters its fetch by joining
     * the listing in as a derived table, which runs the search again for every contained
     * association - four of them here, so six runs where there should be two.
     *
     * The strategy is therefore pinned in the contain, and this is what notices when a hasMany is
     * added to it without one.
     *
     * @return void
     * @link \App\Controller\CustomersController::index()
     */
    public function testIndexAnswersTheAdvancedSearchTwiceAtMost(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);

        $searches = 0;
        $connection->getDriver()->setLogger(new class ($searches) extends AbstractLogger {
            /**
             * @param int $searches Times the search has been answered.
             */
            public function __construct(private int &$searches)
            {
            }

            /**
             * @inheritDoc
             */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                $query = $context['query'] ?? null;
                if (!$query instanceof LoggedQuery) {
                    return;
                }

                if (str_contains((string)($query->jsonSerialize()['query'] ?? ''), 'websearch_to_tsquery')) {
                    $this->searches++;
                }
            }
        });

        $this->login();
        $this->get('/customers?advanced_search=1&search=Lorem');

        $connection->getDriver()->setLogger(new NullLogger());

        $this->assertResponseOk();
        $this->assertLessThanOrEqual(2, $searches, 'The advanced search is being answered more than once per query.');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(__('Obligation Until'));
    }

    /**
     * Test that the related contracts show the latest obligation date of their contract versions.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewShowsLatestObligationUntilOfContractVersions(): void
    {
        // later than the 2022-11-30 obligation of the fixture contract version, but still in the past
        $this->addContractVersion('2023-06-30');

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertObligationCell('', (string)new Date('2023-06-30'));
    }

    /**
     * Test that an obligation date in the future is highlighted, as it is on the contract detail.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewHighlightsFutureObligationUntil(): void
    {
        $futureObligationUntil = Date::now()->addYears(1);
        $this->addContractVersion($futureObligationUntil->toDateString());

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertObligationCell('color: red;', (string)$futureObligationUntil);
    }

    /**
     * Test that a contract without any obligation renders an empty cell.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewRendersEmptyObligationUntilWithoutObligation(): void
    {
        $contractVersionsTable = $this->getTableLocator()->get('ContractVersions');
        $contractVersionsTable->updateAll(['obligation_until' => null], ['contract_id' => self::CONTRACT_ID]);

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertObligationCell('', '');
    }

    /**
     * The form for a new customer renders.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/customers/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing customer renders.
     *
     * @return void
     * @link \App\Controller\CustomersController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/edit');

        $this->assertResponseOk();
    }

    /**
     * A customer filled in on the form is really stored, and the operator is sent to the record
     * they just made.
     *
     * Rendering the form proves the page is there; this proves the way through it works. Everything
     * between the two - marshalling, validation, the application rules and the save - only ever
     * runs on a request that carries data, and a controller test that never posts one leaves the
     * whole of it unasked.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddStoresACustomer(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('customers', 'nid');

        $this->post('/customers/add', [
            'last_name' => 'Smith',
            'first_name' => 'John',
            'dealer' => CustomerDealer::Never->value,
            'invoice_delivery_type' => CustomerInvoiceDeliveryType::Email->value,
            'accounting_profile_id' => self::ACCOUNTING_PROFILE_ID,
        ]);

        $this->assertRedirect();
        $customers = $this->getTableLocator()->get('Customers');
        /** @var \App\Model\Entity\Customer $stored */
        $stored = $customers->find()->where(['last_name' => 'Smith'])->firstOrFail();
        $this->assertSame('John', $stored->first_name);
        $this->assertRedirectContains('/customers/' . $stored->id);
    }

    /**
     * A customer the rules refuse is not stored, and the operator is given the form back rather
     * than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddRefusesACustomerTheRulesRejects(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->advanceIdentity('customers', 'nid');

        $customers = $this->getTableLocator()->get('Customers');
        $before = $customers->find()->count();

        // an accounting profile that is not there - the rules have to catch it
        $this->post('/customers/add', [
            'last_name' => 'Smith',
            'dealer' => CustomerDealer::Never->value,
            'invoice_delivery_type' => CustomerInvoiceDeliveryType::Email->value,
            'accounting_profile_id' => '3f2b1a0c-0000-4000-8000-000000000000',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $customers->find()->count());
    }

    /**
     * A customer submitted without an accounting profile is given the form back with the field
     * marked, rather than an error page.
     *
     * Every customer is billed under a profile and the column says so, but nothing used to ask for
     * one before the save - so a form submitted without it reached the database and came back as a
     * not-null violation, which the operator sees as a 500.
     *
     * @return void
     * @link \App\Model\Table\CustomersTable::validationDefault()
     */
    public function testAddRefusesACustomerWithoutAnAccountingProfile(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->advanceIdentity('customers', 'nid');

        $customers = $this->getTableLocator()->get('Customers');
        $before = $customers->find()->count();

        $this->post('/customers/add', [
            'last_name' => 'Smith',
            'dealer' => CustomerDealer::Never->value,
            'invoice_delivery_type' => CustomerInvoiceDeliveryType::Email->value,
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $customers->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\CustomersController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customers/' . self::CUSTOMER_ID . '/edit', ['last_name' => 'Brown']);

        $this->assertRedirect();
        $this->assertSame(
            'Brown',
            $this->getTableLocator()->get('Customers')->get(self::CUSTOMER_ID)->last_name,
        );
    }

    /**
     * A role that is not admin does not get to add a customer.
     *
     * Every other test here logs in as admin, which `config/permissions.php` lets through
     * everything - so none of them can tell a controller that is guarded from one that is not. This
     * asks the authorization layer the only question that matters about it: does a refusal really
     * happen.
     *
     * A refusal is a redirect away rather than a status in the 400s - the middleware sends whoever
     * is not allowed somewhere they are - so what this holds on to is that they do not arrive at
     * the form.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddIsRefusedToANonAdminRole(): void
    {
        $this->login('api');

        $this->get('/customers/add');

        $this->assertRedirect('/');
    }

    /**
     * The same role does get to list customers, which every role is allowed. Without this the test
     * above would pass just as well on a role that is refused everything, and would be saying
     * nothing about the permissions at all.
     *
     * @return void
     * @link \App\Controller\CustomersController::index()
     */
    public function testIndexIsAllowedToANonAdminRole(): void
    {
        $this->login('api');

        $this->get('/customers');

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the customer really goes depends on what else
     * still references them, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\CustomersController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customers/' . self::CUSTOMER_ID . '/delete');

        $this->assertRedirect();
    }

    /**
     * The print page renders its document type selection.
     *
     * @return void
     * @link \App\Controller\CustomersController::print()
     */
    public function testPrint(): void
    {
        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/print');

        $this->assertResponseOk();
    }
}
