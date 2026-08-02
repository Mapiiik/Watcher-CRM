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
