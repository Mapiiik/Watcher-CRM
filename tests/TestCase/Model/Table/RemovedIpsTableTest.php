<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RemovedIpsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RemovedIpsTable Test Case
 */
class RemovedIpsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RemovedIpsTable
     */
    protected $RemovedIps;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.RemovedIps',
        'app.Customers',
        'app.Queues',
        'app.Devices',
        'app.Dealers',
        'app.Brokerages',
        'app.Contracts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('RemovedIps') ? [] : ['className' => RemovedIpsTable::class];
        $this->RemovedIps = $this->getTableLocator()->get('RemovedIps', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->RemovedIps);

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
