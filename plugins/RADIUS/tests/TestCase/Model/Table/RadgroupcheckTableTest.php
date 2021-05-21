<?php
declare(strict_types=1);

namespace RADIUS\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RADIUS\Model\Table\RadgroupcheckTable;

/**
 * RADIUS\Model\Table\RadgroupcheckTable Test Case
 */
class RadgroupcheckTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RADIUS\Model\Table\RadgroupcheckTable
     */
    protected $Radgroupcheck;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.RADIUS.Radgroupcheck',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Radgroupcheck') ? [] : ['className' => RadgroupcheckTable::class];
        $this->Radgroupcheck = $this->getTableLocator()->get('Radgroupcheck', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Radgroupcheck);

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
     * Test defaultConnectionName method
     *
     * @return void
     */
    public function testDefaultConnectionName(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
