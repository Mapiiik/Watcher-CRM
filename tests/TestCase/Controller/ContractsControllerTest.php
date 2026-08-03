<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

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
}
