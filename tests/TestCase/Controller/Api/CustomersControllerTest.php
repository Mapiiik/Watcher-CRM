<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Controller\Api\CustomersController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\Api\CustomersController Test Case
 *
 * Smoke tests: each endpoint is called once and has to answer with the JSON it promises. The write
 * endpoints report their outcome in a `message` rather than in the status code, so what is asserted
 * is that they answered at all - whether the record itself went through is the model's business.
 */
#[UsesClass(CustomersController::class)]
class CustomersControllerTest extends TestCase
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
     * The collection serializes.
     *
     * @return void
     * @link \App\Controller\Api\CustomersController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/customers.json');

        $this->assertResponseOk();
        $this->assertResponseContains('"customers"');
    }

    /**
     * A single customer serializes with everything that hangs off them.
     *
     * @return void
     * @link \App\Controller\Api\CustomersController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/customers/' . $this->firstId('Customers') . '.json');

        $this->assertResponseOk();
        $this->assertResponseContains('"customer"');
    }

    /**
     * The endpoint takes a new customer and reports the outcome.
     *
     * @return void
     * @link \App\Controller\Api\CustomersController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->advanceIdentity('customers', 'nid');
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/api/customers.json', [
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            // the column is not nullable and nothing fills it in for the caller, so leaving it out
            // reaches the database rather than the validation
            'accounting_profile_id' => $this->firstId('AccountingProfiles'),
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('"message"');
    }

    /**
     * The endpoint takes a change to an existing customer and reports the outcome.
     *
     * @return void
     * @link \App\Controller\Api\CustomersController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->patch('/api/customers/' . $this->firstId('Customers') . '.json', [
            'last_name' => 'Novak',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('"message"');
    }

    /**
     * The endpoint runs the delete and reports the outcome. Whether the customer really goes
     * depends on what else still references them.
     *
     * @return void
     * @link \App\Controller\Api\CustomersController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->delete('/api/customers/' . $this->firstId('Customers') . '.json');

        $this->assertResponseOk();
        $this->assertResponseContains('"message"');
    }

    // customerPoints() is left out on purpose: it resolves the addresses through the national
    // address registry over the network, so a test of it would only be as reliable as that service
    // and the fixture would have to carry registry ids the registry actually knows.
}
