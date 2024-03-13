<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CustomersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CustomersTable Test Case
 */
class CustomersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CustomersTable
     */
    protected $Customers;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.TaxRates',
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
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Customers') ? [] : ['className' => CustomersTable::class];
        $this->Customers = $this->getTableLocator()->get('Customers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Customers);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\CustomersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\CustomersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
