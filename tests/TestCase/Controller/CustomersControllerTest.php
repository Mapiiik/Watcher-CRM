<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CustomersController;
use App\Model\Enum\CustomerDealer;
use App\Model\Enum\CustomerInvoiceDeliveryType;
use App\Test\TestCase\BusinessRegister\Source\StubSource;
use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Database\Connection;
use Cake\Database\Log\LoggedQuery;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
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
    use ConfigureTestTrait;
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
        'app.TaskCollaborators',
        'app.DealerCommissions',
        'app.FulltextSearchCustomers',
    ];

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();
        Cache::clear('business_register');
        StubSource::reset();

        parent::tearDown();
    }

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
     * The listing finds a customer by what their related records say, which is what the advanced
     * search is for - the address searched for is on no column of the customer.
     *
     * @return void
     * @link \App\Controller\CustomersController::index()
     */
    public function testIndexFindsACustomerByWhatTheirRelatedRecordsSay(): void
    {
        $this->fetchTable('FulltextSearchCustomers')->rebuild();

        $this->login();
        $this->get('/customers?advanced_search=1&search=192.168.11.11');

        $this->assertResponseOk();

        /** @var iterable<\App\Model\Entity\Customer> $customers */
        $customers = $this->viewVariable('customers');
        $found = [];
        foreach ($customers as $customer) {
            $found[] = $customer->id;
        }

        $this->assertContains(self::CUSTOMER_ID, $found);
    }

    /**
     * The advanced search is answered no more than twice per page - once for the listing and once
     * for the count that pages it.
     *
     * The `subquery` strategy CakePHP 5.4 made the default for hasMany runs the whole listing
     * query again for every contained association, four of them here. The strategy is pinned in
     * the contain against that, and this is what notices a hasMany added without one.
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
     * What does not add up on a customer's contracts is shown on their card, gathered over all
     * of them - most of what is wrong with one was done to all of them on the day they were
     * written, and a customer's card is where that is seen at once.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewShowsWhatDoesNotAddUpOnTheirContracts(): void
    {
        $contracts = $this->getTableLocator()->get('Contracts');
        $contract_id = $contracts->find()
            ->where(['Contracts.customer_id' => self::CUSTOMER_ID])
            ->firstOrFail()
            ->get('id');

        $billings = $this->getTableLocator()->get('Billings');
        $billings->deleteAll(['customer_id' => self::CUSTOMER_ID]);

        $service_id = $this->getTableLocator()->get('Services')->find()->firstOrFail()->get('id');

        // a year that nobody is billed for, which is what the check is there to find
        foreach ([['+1 month', '+13 months'], ['+25 months', null]] as [$from, $until]) {
            $billings->saveOrFail($billings->newEntity([
                'customer_id' => self::CUSTOMER_ID,
                'contract_id' => $contract_id,
                'service_id' => $service_id,
                'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
                'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
                'quantity' => 1,
                'separate_invoice' => false,
            ]));
        }

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertCount(1, $this->viewVariable('problems'));

        // gathered over several contracts, so each row has to say which one it is about
        $this->assertResponseContains('These contracts do not add up');
        $this->assertResponseContains('Gap Between Consecutive Billings');
    }

    /**
     * A customer whose contracts add up shows no heading at all - an empty warning would teach
     * everybody to look past the place the real ones appear.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewShowsNothingWhereTheContractsAddUp(): void
    {
        $this->getTableLocator()->get('Billings')->deleteAll(['customer_id' => self::CUSTOMER_ID]);
        $this->getTableLocator()->get('ContractVersions')->deleteAll(['1 = 1']);

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('problems'));
        $this->assertResponseNotContains('These contracts do not add up');
    }

    /**
     * The tasks filed under the customer are listed on their card - one table written once and
     * shown wherever tasks stand beside a record.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewListsTheTasksFiledUnderTheCustomer(): void
    {
        /** @var \App\Model\Entity\Task $task */
        $task = $this->getTableLocator()->get('Tasks')
            ->find()
            ->where(['Tasks.customer_id' => self::CUSTOMER_ID])
            ->firstOrFail();

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        // by identifier rather than by number: a task is numbered from one, and a page carries
        // plenty of small numbers that say nothing about tasks
        $this->assertResponseContains($task->id);
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
     * A new customer is one to synchronize until somebody says otherwise, and the form has to open
     * that way: an operator who never looks at the box gets what everybody had before it existed.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddOpensWithSynchronizationToAccountingTurnedOn(): void
    {
        $this->login();
        $this->get('/customers/add');

        $this->assertResponseOk();
        $this->assertResponseContains(
            '<input type="checkbox" name="sync_to_accounting" value="1" id="sync-to-accounting"'
            . ' checked="checked">',
        );
    }

    /**
     * A box somebody unticks is really stored unticked. It is the whole of what the flag does - the
     * partner is then theirs to keep right in the accounting system - so a save that quietly put it
     * back would send them to the accounting system anyway.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddStoresACustomerNotToBeSynchronized(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('customers', 'nid');

        $this->post('/customers/add', [
            'last_name' => 'Baker',
            'dealer' => CustomerDealer::Never->value,
            'invoice_delivery_type' => CustomerInvoiceDeliveryType::Email->value,
            'accounting_profile_id' => self::ACCOUNTING_PROFILE_ID,
            'sync_to_accounting' => '0',
        ]);

        $this->assertRedirect();
        $customers = $this->getTableLocator()->get('Customers');
        /** @var \App\Model\Entity\Customer $stored */
        $stored = $customers->find()->where(['last_name' => 'Baker'])->firstOrFail();
        $this->assertFalse($stored->sync_to_accounting);
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

    /**
     * An identification number that fails its own check digit is marked as the mistake it is,
     * rather than being left to be read off a bracket among the others.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewMarksAnInvalidIdentityNumberAsAnError(): void
    {
        $customers = $this->getTableLocator()->get('Customers');
        /** @var \App\Model\Entity\Customer $customer */
        $customer = $customers->get(self::CUSTOMER_ID);
        $customer->identity_number = '12345678'; // eight digits, wrong check digit
        $customers->saveOrFail($customer, ['checkRules' => false]);

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('<span class="error-text"> (Invalid)</span>');
    }

    /**
     * A number that holds up is not dressed as a mistake.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewLeavesAValidIdentityNumberUnmarked(): void
    {
        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(' (OK)');
        $this->assertResponseNotContains('<span class="error-text"> (Invalid)</span>');
    }

    /**
     * Picking a company in the register fills the form in and hands it back, without storing
     * anything - the operator has not said to save yet, only which company they meant.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddFillsTheFormInFromTheRegisterWithoutStoring(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'name' => 'NETAIR, s.r.o.',
                'company' => 'NETAIR, s.r.o.',
                'identity_number' => '27496139',
                'vat_number' => 'CZ27496139',
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $customers = $this->getTableLocator()->get('Customers');
        $before = $customers->find()->count();

        $this->post('/customers/add', [
            'refresh' => 'refresh',
            'business_register_source' => 'stub',
            'business_register_search' => 'stub|27496139',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('NETAIR, s.r.o.');
        $this->assertResponseContains('CZ27496139');
        $this->assertSame($before, $customers->find()->count());
    }

    /**
     * A register naming somebody else under the customer's identification number is marked as the
     * mistake it is - the number checks out, so nothing else would show it.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewMarksARegisterNamingSomebodyElse(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'Somebody Else, a.s.'],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);

        $customers = $this->getTableLocator()->get('Customers');
        /** @var \App\Model\Entity\Customer $customer */
        $customer = $customers->get(self::CUSTOMER_ID);
        $customer->company = 'NETAIR, s.r.o.';
        $customer->identity_number = '27496139';
        $customers->saveOrFail($customer);

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('<span class="error-text"> (Somebody Else, a.s.)</span>');
    }

    /**
     * The same name written the way another register writes it is not a mistake, so it is left
     * alone - a legal form abbreviated differently is the same company.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewLeavesAMatchingNameUnmarked(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR s. r. o.'],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);

        $customers = $this->getTableLocator()->get('Customers');
        /** @var \App\Model\Entity\Customer $customer */
        $customer = $customers->get(self::CUSTOMER_ID);
        $customer->company = 'NETAIR, s.r.o.';
        $customer->identity_number = '27496139';
        $customers->saveOrFail($customer);

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(' (NETAIR s. r. o.)');
        $this->assertResponseNotContains('<span class="error-text"> (NETAIR s. r. o.)</span>');
    }

    /**
     * A company with an entry like NETAIR's, which two people sit in the statutory body of.
     *
     * @return void
     */
    private function givenACompanyWithTwoOfficers(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'name' => 'NETAIR, s.r.o.',
                'company' => 'NETAIR, s.r.o.',
                'identity_number' => '27496139',
                'officers' => [
                    [
                        'key' => 'marko',
                        'title' => null,
                        'first_name' => 'Marko',
                        'last_name' => 'Jujnović',
                        'suffix' => null,
                        'date_of_birth' => '1970-05-31',
                    ],
                    [
                        'key' => 'jana',
                        'title' => null,
                        'first_name' => 'Jana',
                        'last_name' => 'Janatová',
                        'suffix' => null,
                        'date_of_birth' => '1983-08-17',
                    ],
                ],
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
    }

    /**
     * Where several people sit in the statutory body, the form offers them and fills in nobody of
     * its own accord - the name goes onto a contract as who the company was represented by.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddOffersTheOfficersOfACompanyRepresentedBySeveral(): void
    {
        $this->givenACompanyWithTwoOfficers();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customers/add', [
            'refresh' => 'refresh',
            'business_register_source' => 'stub',
            'business_register_search' => 'stub|27496139',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('name="business_register_officer"');
        $this->assertResponseContains('Jujnović');
        $this->assertResponseContains('Janatová');
        // the picked company has to come back with the form, or the next submit loses it
        $this->assertResponseContains('value="stub|27496139"');
    }

    /**
     * Choosing one of them fills their name in, and still does not store the customer.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddFillsInTheChosenOfficer(): void
    {
        $this->givenACompanyWithTwoOfficers();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $customers = $this->getTableLocator()->get('Customers');
        $before = $customers->find()->count();

        $this->post('/customers/add', [
            'refresh' => 'refresh',
            'business_register_source' => 'stub',
            'business_register_search' => 'stub|27496139',
            'business_register_officer' => 'jana',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('value="Jana"');
        $this->assertResponseContains('value="Janatová"');
        $this->assertResponseContains('1983-08-17');
        $this->assertSame($before, $customers->find()->count());
    }

    /**
     * A choice left over from a company picked before this one names nobody here, so what the
     * entry says of itself stands - which for a company of several is no name at all.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddIgnoresAnOfficerTheCompanyDoesNotHave(): void
    {
        $this->givenACompanyWithTwoOfficers();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customers/add', [
            'refresh' => 'refresh',
            'business_register_source' => 'stub',
            'business_register_search' => 'stub|27496139',
            'business_register_officer' => 'somebody-from-another-company',
        ]);

        $this->assertResponseOk();
        $this->assertResponseNotContains('value="Jana"');
        $this->assertResponseNotContains('value="Marko"');
    }

    /**
     * One person sitting is already filled in, so there is nothing to choose and no list to show.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddOffersNoChoiceWhereOnlyOneSits(): void
    {
        StubSource::$entries = [
            [
                'reference' => '28992121',
                'name' => 'Statek Polánka s.r.o.',
                'company' => 'Statek Polánka s.r.o.',
                'first_name' => 'Marek',
                'last_name' => 'Zbořil',
                'officers' => [
                    [
                        'key' => 'marek',
                        'title' => 'Ing.',
                        'first_name' => 'Marek',
                        'last_name' => 'Zbořil',
                        'suffix' => null,
                        'date_of_birth' => '1973-10-31',
                    ],
                ],
            ],
        ];
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customers/add', [
            'refresh' => 'refresh',
            'business_register_source' => 'stub',
            'business_register_search' => 'stub|28992121',
        ]);

        $this->assertResponseOk();
        $this->assertResponseNotContains('name="business_register_officer"');
        $this->assertResponseContains('value="Zbořil"');
    }

    /**
     * A reference the register no longer holds is said out loud rather than quietly filling the
     * form in with nothing.
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAddSaysSoWhenTheRegisterNoLongerHoldsTheCompany(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customers/add', [
            'refresh' => 'refresh',
            'business_register_source' => 'stub',
            'business_register_search' => 'stub|00000000',
        ]);

        $this->assertResponseOk();
        // the form is rendered rather than redirected to, so the message is shown on the way out
        // instead of waiting in the session
        $this->assertResponseContains('The company is no longer held by the register.');
    }
}
