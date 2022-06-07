<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ContractsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ContractsTable Test Case
 */
class ContractsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractsTable
     */
    protected $ContractsTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Contracts',
        'app.Customers',
        'app.Addresses',
        'app.ServiceTypes',
        'app.Brokerages',
        'app.Billings',
        'app.BorrowedEquipments',
        'app.Ips',
        'app.RemovedIps',
        'app.SoldEquipments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Contracts') ? [] : ['className' => ContractsTable::class];
        $this->ContractsTable = $this->fetchTable('Contracts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ContractsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ContractsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ContractsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
