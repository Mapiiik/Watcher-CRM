<?php
declare(strict_types=1);

namespace RADIUS\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RADIUS\Model\Table\RadreplyTable;

/**
 * RADIUS\Model\Table\RadreplyTable Test Case
 */
class RadreplyTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RADIUS\Model\Table\RadreplyTable
     */
    protected $Radreply;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.RADIUS.Radreply',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Radreply') ? [] : ['className' => RadreplyTable::class];
        $this->Radreply = $this->getTableLocator()->get('Radreply', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Radreply);

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
