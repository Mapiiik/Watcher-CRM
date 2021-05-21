<?php
declare(strict_types=1);

namespace RADIUS\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RADIUS\Model\Table\RadcheckTable;

/**
 * RADIUS\Model\Table\RadcheckTable Test Case
 */
class RadcheckTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RADIUS\Model\Table\RadcheckTable
     */
    protected $Radcheck;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.RADIUS.Radcheck',
        'plugin.RADIUS.CustomerConnections',
        'plugin.RADIUS.Customers',
        'plugin.RADIUS.Contracts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Radcheck') ? [] : ['className' => RadcheckTable::class];
        $this->Radcheck = $this->getTableLocator()->get('Radcheck', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Radcheck);

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

    /**
     * Test defaultConnectionName method
     *
     * @return void
     */
    public function testDefaultConnectionName(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
