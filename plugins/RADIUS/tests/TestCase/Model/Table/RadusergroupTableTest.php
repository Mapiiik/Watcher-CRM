<?php
declare(strict_types=1);

namespace RADIUS\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RADIUS\Model\Table\RadusergroupTable;

/**
 * RADIUS\Model\Table\RadusergroupTable Test Case
 */
class RadusergroupTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RADIUS\Model\Table\RadusergroupTable
     */
    protected $Radusergroup;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
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
        $config = $this->getTableLocator()->exists('Radusergroup') ? [] : ['className' => RadusergroupTable::class];
        $this->Radusergroup = $this->getTableLocator()->get('Radusergroup', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Radusergroup);

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
