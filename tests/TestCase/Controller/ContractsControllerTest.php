<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Log\LoggedQuery;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
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
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.SoldEquipments',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
    ];

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
     * The print page renders its document type selection. It eager loads most of what hangs off a
     * contract, so it is the widest query in this controller.
     *
     * @return void
     * @link \App\Controller\ContractsController::print()
     */
    public function testPrint(): void
    {
        $this->login();
        $this->get('/contracts/print/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
    }

    /**
     * The number of the contract to be terminated is typed by hand, because a contract concluded
     * before the renumbering carries a different one than the record does. Both numbers it could
     * be are offered.
     *
     * @return void
     * @link \App\Controller\ContractsController::print()
     */
    public function testPrintSuggestsNumbersForTheContractToBeTerminated(): void
    {
        $contract = $this->fetchTable('Contracts')->get($this->firstId('Contracts'), contain: ['Customers']);

        $this->login();
        $this->get('/contracts/print/' . $contract->id);

        $this->assertResponseOk();
        $this->assertResponseContains('<datalist id="contract-numbers-to-be-terminated">');
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

        // Said here rather than read from the environment, which the CI has none of.
        Configure::write('Nms.url', 'https://nms.example.com');

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
}
