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
    protected $Contracts;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Contracts',
        'app.Customers',
        'app.InstallationAddresses',
        'app.ServiceTypes',
        'app.InstallationTechnicians',
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
        $this->Contracts = $this->getTableLocator()->get('Contracts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Contracts);

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
