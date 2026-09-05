<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractsController;
use App\Model\Enum\ContractPrintType;
use App\Model\Table\BillingsTable;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Log\LoggedQuery;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;

/**
 * App\Controller\ContractsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ContractsController::class)]
class ContractsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The contract the fixtures carry, whose installation address has coordinates.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The access point that contract names, which lives in the other application.
     *
     * @var string
     */
    private const ACCESS_POINT_ID = 'feedb343-cea8-423f-a409-de4331354217';

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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.ContractVersions',
        'app.ContractVersionProposals',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.SoldEquipments',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.TaskCollaborators',
    ];

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        // What a test said about the other application would otherwise answer the next one.
        Cache::clear('api_client');
        Configure::write('Nms.url', '');
        Configure::write('Nms.key', '');

        parent::tearDown();
    }

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ContractsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/contracts');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ContractsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/contracts?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a contract renders, both on its own and nested under its customer - the nested
     * form is a route of its own rather than the same page reached differently.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $contractId = $this->firstId('Contracts');
        $this->get('/contracts/view/' . $contractId);

        $this->assertResponseOk();

        $customerId = $this->getTableLocator()->get('Contracts')->get($contractId)->get('customer_id');
        $this->get('/customers/' . $customerId . '/contracts/' . $contractId);

        $this->assertResponseOk();

        // The addresses, the networks and the RADIUS accounts here all fetch themselves. The
        // accounts are drawn by a cell, which renders in a view of its own and so cannot ask
        // for the script - this page has to, and only opening it says whether it did.
        $this->assertResponseContains('js/lazy-load.js');
    }

    /**
     * The findings are fetched on their own, which made them an action of their own - and an
     * action of its own is a thing that can quietly be shut to somebody the page is open to.
     * Whoever may look at the contract may ask what does not add up on it.
     *
     * @param string $role Role to sign in as.
     * @return void
     * @link \App\Controller\ContractsController::problems()
     */
    #[DataProvider('everyRole')]
    public function testWhoeverMayLookAtTheContractMayAskWhatIsWrongWithIt(string $role): void
    {
        $contract_id = $this->firstId('Contracts');

        $this->login($role);
        $this->get('/contracts/view/' . $contract_id);
        $viewing = $this->_response?->getStatusCode();

        $this->login($role);
        $this->get('/contracts/problems/' . $contract_id);

        $this->assertSame(
            $viewing,
            $this->_response?->getStatusCode(),
            sprintf('%s is shown the contract but not what does not add up on it.', $role),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function everyRole(): array
    {
        $roles = [
            'admin',
            'sales-manager',
            'network-manager',
            'bookkeeper',
            'sales-representative',
            'network-technician',
            'customer-service-technician',
        ];

        return array_combine($roles, array_map(fn(string $role): array => [$role], $roles));
    }

    /**
     * The checks come to about as much work as the rest of the page put together, so the page
     * must not be waiting on them - it draws first and asks for them afterwards.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testViewAsksForTheFindingsRatherThanWaitingForThem(): void
    {
        $contract_id = $this->firstId('Contracts');

        $this->login();
        $this->get('/contracts/view/' . $contract_id);

        $this->assertResponseOk();
        $this->assertNull($this->viewVariable('problems'), 'The page ran the checks itself.');
        $this->assertResponseContains('/contracts/problems/' . $contract_id);
        $this->assertResponseContains('js/lazy-load.js');
    }

    /**
     * A contract that does not add up says so at the top, where it is being worked on, rather
     * than only in a listing of the whole file that nobody opens while fixing one record.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testViewShowsWhatDoesNotAddUpOnTheContract(): void
    {
        $contract_id = $this->firstId('Contracts');
        $this->noPaperworkIsPending($contract_id);
        $billings = $this->getTableLocator()->get('Billings');
        $billings->deleteAll(['contract_id' => $contract_id]);

        $service_id = $this->getTableLocator()->get('Services')->find()->firstOrFail()->get('id');
        $customer_id = $this->getTableLocator()->get('Contracts')->get($contract_id)->get('customer_id');

        // a year that nobody is billed for, which is what the check is there to find
        foreach ([['+1 month', '+13 months'], ['+25 months', null]] as [$from, $until]) {
            $billings->saveOrFail($billings->newEntity([
                'customer_id' => $customer_id,
                'contract_id' => $contract_id,
                'service_id' => $service_id,
                'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
                'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
                'quantity' => 1,
                'separate_invoice' => false,
            ]));
        }

        $this->login();
        $this->get('/contracts/problems/' . $contract_id);

        $this->assertResponseOk();
        $this->assertCount(1, $this->viewVariable('problems'));
        $this->assertResponseContains('Gap Between Consecutive Billings');

        // drawn on its own, so it arrives as the block itself rather than as a page
        $this->assertResponseNotContains('<html');
    }

    /**
     * The address checks are asked about the contract too. Most of them are about a customer's
     * address book and leave themselves out, but the one whose subject is the contracts
     * answers - and a contract with nowhere to install is exactly a contract-page finding.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testViewShowsWhatTheAddressChecksHaveOnTheContract(): void
    {
        $contract_id = $this->firstId('Contracts');
        $contracts = $this->getTableLocator()->get('Contracts');

        // the service type on it already requires an installation address
        $contracts->updateAll(['installation_address_id' => null], ['id' => $contract_id]);
        $this->getTableLocator()->get('Billings')->deleteAll(['contract_id' => $contract_id]);

        $this->login();
        $this->get('/contracts/problems/' . $contract_id);

        $this->assertResponseOk();

        $reported = array_map(
            fn(array $problem): string => $problem['check']->id(),
            (array)$this->viewVariable('problems'),
        );

        $this->assertContains('missing_installation_address', $reported);

        // and the ones that cannot speak about one contract stayed out of it
        $this->assertNotContains('duplicate_address', $reported);
        $this->assertNotContains('unclear_billing_address', $reported);
    }

    /**
     * A contract with nothing wrong shows no heading at all - an empty warning would teach
     * everybody to look past the place the real ones appear.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testViewShowsNothingWhereTheContractAddsUp(): void
    {
        $contract_id = $this->firstId('Contracts');
        $this->noPaperworkIsPending($contract_id);

        // One billing, open ended, covering today: a contract providing services with nothing
        // billed for them is itself a finding, so leaving it with none would not be clean.
        $billings = $this->getTableLocator()->get('Billings');
        $billings->deleteAll(['contract_id' => $contract_id]);
        $billings->saveOrFail($billings->newEntity([
            'customer_id' => $this->getTableLocator()->get('Contracts')->get($contract_id)->get('customer_id'),
            'contract_id' => $contract_id,
            'service_id' => $this->getTableLocator()->get('Services')->find()->firstOrFail()->get('id'),
            'billing_from' => Date::now()->modify('-1 year')->format('Y-m-d'),
            'billing_until' => null,
            'quantity' => 1,
            'separate_invoice' => false,
        ]), [BillingsTable::ALLOW_CLOSED_PERIODS => true]);

        $this->login();
        $this->get('/contracts/problems/' . $contract_id);

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('problems'));
        $this->assertResponseNotContains('probably not right');
    }

    /**
     * A banner of a few rows is shorter than the height it would be cut to, so it is shown whole
     * and without an opener - one that uncovers nothing would only teach people to ignore it.
     *
     * @return void
     * @link \App\Controller\ContractsController::problems()
     */
    public function testAShortBannerIsShownWholeWithNothingToOpen(): void
    {
        $contract_id = $this->firstId('Contracts');
        $this->noPaperworkIsPending($contract_id);
        $billings = $this->getTableLocator()->get('Billings');
        $billings->deleteAll(['contract_id' => $contract_id]);

        $service_id = $this->getTableLocator()->get('Services')->find()->firstOrFail()->get('id');
        $customer_id = $this->getTableLocator()->get('Contracts')->get($contract_id)->get('customer_id');

        foreach ([['+1 month', '+13 months'], ['+25 months', null]] as [$from, $until]) {
            $billings->saveOrFail($billings->newEntity([
                'customer_id' => $customer_id,
                'contract_id' => $contract_id,
                'service_id' => $service_id,
                'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
                'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
                'quantity' => 1,
                'separate_invoice' => false,
            ]));
        }

        $this->login();
        $this->get('/contracts/problems/' . $contract_id);

        $this->assertResponseOk();
        $this->assertCount(1, $this->viewVariable('problems'));
        $this->assertResponseNotContains('problems-toggle');
        $this->assertResponseNotContains('clamped');
    }

    /**
     * Two checks of a row each is the common shape of a contract that does not add up, and it
     * is shorter than the height it would be cut to - cutting it would save a few pixels at
     * the price of a click.
     *
     * @return void
     * @link \App\Controller\ContractsController::problems()
     */
    public function testTwoFindingsOfOneRowEachAreStillShownWhole(): void
    {
        $contract_id = $this->firstId('Contracts');
        $this->noPaperworkIsPending($contract_id);

        // nothing billed and nowhere to install it: one row from each of two checks
        $this->getTableLocator()->get('Billings')->deleteAll(['contract_id' => $contract_id]);
        $this->getTableLocator()->get('Contracts')
            ->updateAll(['installation_address_id' => null], ['id' => $contract_id]);

        $this->login();
        $this->get('/contracts/problems/' . $contract_id);

        $this->assertResponseOk();
        $this->assertCount(2, $this->viewVariable('problems'));
        $this->assertResponseNotContains('problems-toggle');
        $this->assertResponseNotContains('clamped');
    }

    /**
     * The card carries two task tables: the ones filed under this contract, and the customer's
     * others. Only the second names a contract per row - on the first it would say the same
     * thing every time.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testViewListsTheTasksOfTheContractAndTheCustomersOthers(): void
    {
        $tasks = $this->getTableLocator()->get('Tasks');

        // the one the fixtures carry is filed under the customer without naming a contract
        /** @var \App\Model\Entity\Task $elsewhere */
        $elsewhere = $tasks->find()->firstOrFail();

        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('tasks', 'nid');

        /** @var \App\Model\Entity\Task $own */
        $own = $tasks->saveOrFail($tasks->newEntity([
            'task_type_id' => $this->firstId('TaskTypes'),
            'task_state_id' => $this->firstId('TaskStates'),
            'subject' => 'Written by the test',
            'priority' => 0,
            'customer_id' => $elsewhere->customer_id,
            'contract_id' => self::CONTRACT_ID,
        ]));

        $this->login();
        $this->get('/contracts/view/' . self::CONTRACT_ID);

        $this->assertResponseOk();
        // by identifier rather than by number: a task is numbered from one, and a page carries
        // plenty of small numbers that say nothing about tasks
        $this->assertResponseContains($own->id);
        $this->assertResponseContains($elsewhere->id);
    }

    /**
     * The detail fetches the contract's own billings and nobody else's.
     *
     * The `subquery` strategy joins its filtering derived table in under the alias of the source
     * table, so this contain reaching `Contracts` again overwrites it and every billing there is
     * gets fetched. It comes back with the right ones grouped, so nothing looks wrong - what
     * gives it away is how much was read to get there, which is what this counts.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testViewFetchesOnlyTheContractsOwnBillings(): void
    {
        $contractId = $this->firstId('Contracts');
        // counted through the finder the detail uses, so that the number is what it should read
        // and not what the contract has altogether
        $own = $this->getTableLocator()->get('Billings')
            ->find('activeOrFuture')
            ->where(['Billings.contract_id' => $contractId])
            ->count();

        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);

        $rows = 0;
        $connection->getDriver()->setLogger(new class ($rows) extends AbstractLogger {
            /**
             * @param int $rows Rows read from the billings table.
             */
            public function __construct(private int &$rows)
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

                $data = $query->jsonSerialize();
                if (str_contains((string)($data['query'] ?? ''), 'FROM billings')) {
                    $this->rows += (int)($data['numRows'] ?? 0);
                }
            }
        });

        $this->login();
        $this->get('/contracts/view/' . $contractId);

        $connection->getDriver()->setLogger(new NullLogger());

        $this->assertResponseOk();
        $this->assertLessThanOrEqual(
            $own,
            $rows,
            'The contract detail is reading billings that belong to other contracts.',
        );
    }

    /**
     * The form for a new contract renders.
     *
     * @return void
     * @link \App\Controller\ContractsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/contracts/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing contract renders.
     *
     * @return void
     * @link \App\Controller\ContractsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/contracts/edit/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
    }

    /**
     * Put a usable subscriber verification code format on the given service type.
     *
     * The controller builds the code by selecting the format as a SQL expression, so the column
     * holds a fragment of SQL rather than a template. What the fixture carries there is the baker's
     * placeholder prose, which the database refuses to parse - a contract of that service type
     * cannot be added at all until the column says something a query can be built from.
     *
     * @param string $serviceTypeId Service type the contract under test belongs to.
     * @return void
     */
    private function giveTheServiceTypeAUsableCodeFormat(string $serviceTypeId): void
    {
        $serviceTypes = $this->getTableLocator()->get('ServiceTypes');
        $serviceType = $serviceTypes->get($serviceTypeId);
        $serviceType->set('subscriber_verification_code_format', "'CODE-' || nid");
        $serviceTypes->saveOrFail($serviceType);
    }

    /**
     * Take the fixture's proposal off the contract.
     *
     * It was drawn up and never sent, which is a finding of its own - and the tests that count the
     * findings on a card are about how the banner behaves when there are one or two of them, not
     * about which ones the fixture happens to carry.
     *
     * @param string $contract_id The contract under test.
     * @return void
     */
    private function noPaperworkIsPending(string $contract_id): void
    {
        $this->getTableLocator()->get('ContractVersionProposals')
            ->deleteAll(['contract_id' => $contract_id]);
    }

    /**
     * Let the other application keep the place the fixtures' contract names.
     *
     * The rules refuse a contract naming a place Watcher NMS does not keep, and a test is not to
     * ask the network what it keeps - so the answer is written where the client reads it from.
     *
     * @return void
     */
    private function letTheNetworkKeepTheAccessPoint(): void
    {
        Cache::write(
            'access_points',
            [['id' => self::ACCESS_POINT_ID, 'name' => 'Mast']],
            'api_client',
        );

        Configure::write('Nms.url', 'https://nms.example.com');
        Configure::write('Nms.key', 'secret');
    }

    /**
     * A contract filled in on the form is really stored.
     *
     * Rendering the form proves the page is there; this proves the way through it works.
     * Marshalling, validation, the application rules and the save only ever run on a request that
     * carries data, and the rules of this table are where a service type is asked what it requires.
     *
     * @return void
     * @link \App\Controller\ContractsController::add()
     */
    public function testAddStoresAContract(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // the fixtures write the identity column with the values they carry, which leaves the
        // identity itself where it started
        $this->advanceIdentity('contracts', 'nid');

        /** @var \App\Model\Entity\Contract $existing */
        $existing = $this->getTableLocator()->get('Contracts')->get($this->firstId('Contracts'));
        $this->giveTheServiceTypeAUsableCodeFormat((string)$existing->service_type_id);
        $this->letTheNetworkKeepTheAccessPoint();

        $this->post('/contracts/add', [
            'number' => 'S-2026-0001',
            'customer_id' => $existing->customer_id,
            'service_type_id' => $existing->service_type_id,
            'contract_state_id' => $existing->contract_state_id,
            // the service type in the fixtures requires both of these, which is what the rules
            // asking it are for
            'installation_address_id' => $existing->installation_address_id,
            'access_point_id' => $existing->access_point_id,
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\Contract $stored */
        $stored = $this->getTableLocator()->get('Contracts')
            ->find()
            ->where(['number' => 'S-2026-0001'])
            ->firstOrFail();
        $this->assertSame($existing->customer_id, $stored->customer_id);
    }

    /**
     * A service type that requires an installation address refuses a contract without one, and the
     * operator is given the form back rather than a redirect suggesting it went through.
     *
     * @return void
     * @link \App\Controller\ContractsController::add()
     */
    public function testAddRefusesAContractMissingWhatTheServiceTypeRequires(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->advanceIdentity('contracts', 'nid');

        $contracts = $this->getTableLocator()->get('Contracts');
        $existing = $contracts->get($this->firstId('Contracts'));
        $before = $contracts->find()->count();

        $this->post('/contracts/add', [
            'number' => 'S-2026-0002',
            'customer_id' => $existing->customer_id,
            'service_type_id' => $existing->service_type_id,
            'contract_state_id' => $existing->contract_state_id,
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $contracts->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\ContractsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->letTheNetworkKeepTheAccessPoint();

        $contractId = $this->firstId('Contracts');
        $this->post('/contracts/edit/' . $contractId, ['number' => 'S-2026-0003']);

        $this->assertRedirect();
        $this->assertSame(
            'S-2026-0003',
            $this->getTableLocator()->get('Contracts')->get($contractId)->number,
        );
    }

    /**
     * The delete action runs and redirects. Whether the contract really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ContractsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contracts/delete/' . $this->firstId('Contracts'));

        $this->assertRedirect();
    }

    /**
     * The print page renders. It shows the contract, its versions and the proposals drawn up on it,
     * because a document is chosen by choosing which proposal it is for.
     *
     * @return void
     * @link \App\Controller\ContractsController::print()
     */
    public function testPrint(): void
    {
        $this->login();
        $this->get('/contracts/print/7f76dc3f-a11b-4109-958b-4b0382545a66');

        $this->assertResponseOk();
        $this->assertResponseContains(__('Contract Versions'));
        $this->assertResponseContains(__('Proposals'));
        $this->assertResponseContains(__('New Proposal'));
    }

    /**
     * Choosing a proposal offers the documents it may be printed as, and no others.
     *
     * @return void
     * @link \App\Controller\ContractsController::print()
     */
    public function testPrintOffersWhatTheProposalMayBePrintedAs(): void
    {
        $this->login();
        $this->get('/contracts/print/7f76dc3f-a11b-4109-958b-4b0382545a66'
            . '?proposal_id=c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60');

        $this->assertResponseOk();
        $this->assertResponseContains(__('Document Type'));
        $this->assertResponseContains(ContractPrintType::ContractSummary->label());
        // It replaces nothing and ends nothing, so neither of those documents is on offer.
        $this->assertResponseNotContains(ContractPrintType::ContractNewX->label());
        $this->assertResponseNotContains(ContractPrintType::ContractTermination->label());
    }

    /**
     * The number of the contract being terminated is suggested where it is now written down - on
     * the proposal, which is what keeps it after the paper is printed. It used to be typed in at
     * every printing and thrown away with the query string.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::add()
     */
    public function testTheNumberToBeTerminatedIsOfferedOnTheProposal(): void
    {
        $contract = $this->fetchTable('Contracts')->get($this->firstId('Contracts'), contain: ['Customers']);

        $this->login();
        $this->get('/customers/' . $contract->customer_id . '/contracts/' . $contract->id
            . '/contract-version-proposals/add');

        $this->assertResponseOk();
        $this->assertResponseContains('<option value="' . h($contract->number) . '">');
        $this->assertResponseContains('<option value="' . h($contract->customer->number) . '">');
    }

    /**
     * The map draws both ends of the service, the line between them, and how far that is.
     *
     * The access point lives in the other application and is read through a cache, so the test
     * says what it would have answered rather than reaching for it.
     *
     * @return void
     * @link \App\Controller\ContractsController::map()
     */
    public function testMapDrawsBothEndsAndMeasuresThem(): void
    {
        // A degree of latitude north of the installation address the fixtures place at 1, 1.
        Cache::write(
            'access_points',
            [['id' => self::ACCESS_POINT_ID, 'name' => 'Mast', 'gps_y' => 2.0, 'gps_x' => 1.0]],
            'api_client',
        );

        // Said here rather than read from the environment, which the CI has none of. Both halves
        // of it, because a kept reading is only handed over where there is a Watcher NMS it could
        // have come from.
        Configure::write('Nms.url', 'https://nms.example.com');
        Configure::write('Nms.key', 'secret');

        try {
            $this->login();
            $this->get('/contracts/map/' . self::CONTRACT_ID);

            $this->assertResponseOk();

            /** @var array<string, \Maps\Marker> $markers */
            $markers = $this->viewVariable('mapMarkers');
            $this->assertArrayHasKey('customer', $markers);
            $this->assertArrayHasKey('access_point', $markers);

            $this->assertCount(1, (array)$this->viewVariable('mapPolylines'));
            $this->assertEqualsWithDelta(111194.93, $this->viewVariable('mapDistance'), 0.01);

            // The access point is the other application's, so its bubble leads there.
            $this->assertStringContainsString(
                'https://nms.example.com/access-points/' . self::ACCESS_POINT_ID,
                $markers['access_point']->content,
            );
        } finally {
            Cache::delete('access_points', 'api_client');
            Configure::write('Nms.key', '');
        }
    }

    /**
     * An access point the other application does not know about leaves the customer on the map
     * alone, and nothing to measure against.
     *
     * @return void
     * @link \App\Controller\ContractsController::map()
     */
    public function testMapDrawsTheCustomerWithoutTheAccessPoint(): void
    {
        // the other application answered, and said nothing about this point
        Cache::write('access_points', [], 'api_client');

        try {
            $this->login();
            $this->get('/contracts/map/' . self::CONTRACT_ID);

            $this->assertResponseOk();
            $this->assertSame(['customer'], array_keys((array)$this->viewVariable('mapMarkers')));
            $this->assertSame([], $this->viewVariable('mapPolylines'));
            $this->assertNull($this->viewVariable('mapDistance'));
        } finally {
            Cache::delete('access_points', 'api_client');
        }
    }

    /**
     * Winding a contract up ends the billings that were still running with it.
     *
     * @return void
     * @link \App\Controller\ContractsController::terminateRelatedBillings()
     */
    public function testTerminatingBillingsGivesThemTheDayTheContractStopped(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $stopped = $billings->firstOpenPeriodStart()->addDays(5);
        $this->contractStoppedOn($stopped);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contracts/terminate-related-billings/' . self::CONTRACT_ID);

        $this->assertRedirect();
        $this->assertSame(
            0,
            $billings->find()->where([
                'Billings.contract_id' => self::CONTRACT_ID,
                'Billings.billing_until IS' => null,
            ])->count(),
        );
    }

    /**
     * A contract wound up in a month that has already been invoiced for is another matter: giving
     * the billings that day takes back part of an invoice that has gone out, so it is refused and
     * said out loud rather than done quietly.
     *
     * @return void
     * @link \App\Controller\ContractsController::terminateRelatedBillings()
     */
    public function testBillingsAreNotTerminatedBackIntoAnInvoicedPeriod(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $this->contractStoppedOn($billings->lastClosedPeriodEnd()->subDays(1));

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contracts/terminate-related-billings/' . self::CONTRACT_ID);

        $this->assertRedirect();
        $this->assertGreaterThan(
            0,
            $billings->find()->where([
                'Billings.contract_id' => self::CONTRACT_ID,
                'Billings.billing_until IS' => null,
            ])->count(),
            'A billing was ended inside a period that has been invoiced for.',
        );
    }

    /**
     * Record that the contract stopped on the given day.
     *
     * @param \Cake\I18n\Date $day The day it stopped.
     * @return void
     */
    private function contractStoppedOn(Date $day): void
    {
        $contracts = $this->getTableLocator()->get('Contracts');
        $contract = $contracts->get(self::CONTRACT_ID);
        $contract->termination_date = $day;
        $contracts->saveOrFail($contract, ['checkRules' => false]);
    }
}
