<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Radius\Model\Table\RadusergroupTable;

/**
 * Radius\Model\Table\RadusergroupTable Test Case
 */
class RadusergroupTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Radius\Model\Table\RadusergroupTable
     */
    protected $Radusergroup;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radgroupcheck',
        'plugin.Radius.Radgroupreply',
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
        $this->Radusergroup = $this->fetchTable('Radusergroup', $config);
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
