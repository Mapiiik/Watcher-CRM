<?php
declare(strict_types=1);

namespace RADIUS\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RADIUS\Model\Table\RadgroupreplyTable;

/**
 * RADIUS\Model\Table\RadgroupreplyTable Test Case
 */
class RadgroupreplyTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RADIUS\Model\Table\RadgroupreplyTable
     */
    protected $Radgroupreply;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.RADIUS.Radgroupreply',
        'plugin.RADIUS.Radusergroup',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Radgroupreply') ? [] : ['className' => RadgroupreplyTable::class];
        $this->Radgroupreply = $this->getTableLocator()->get('Radgroupreply', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Radgroupreply);

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
