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
     * @var array
     */
    protected $fixtures = [
        'app.Customers',
        'app.Taxes',
        'app.Addresses',
        'app.Billings',
        'app.BorrowedEquipments',
        'app.Contracts',
        'app.Emails',
        'app.Ips',
        'app.LabelCustomers',
        'app.Logins',
        'app.Phones',
        'app.RemovedIps',
        'app.SoldEquipments',
        'app.Tasks',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
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
    public function tearDown(): void
    {
        unset($this->Customers);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
