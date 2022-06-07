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
    protected $RemovedIpsTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.RemovedIps',
        'app.Customers',
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
        $this->RemovedIpsTable = $this->fetchTable('RemovedIps', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->RemovedIpsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\RemovedIpsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\RemovedIpsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
