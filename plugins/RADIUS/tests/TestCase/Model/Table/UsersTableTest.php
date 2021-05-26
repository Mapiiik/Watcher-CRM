<?php
declare(strict_types=1);

namespace RADIUS\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RADIUS\Model\Table\UsersTable;

/**
 * RADIUS\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RADIUS\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.RADIUS.Users',
        'plugin.RADIUS.Customers',
        'plugin.RADIUS.Contracts',
        'plugin.RADIUS.Radcheck',
        'plugin.RADIUS.Radreply',
        'plugin.RADIUS.Radusergroup',
        'plugin.RADIUS.Radpostauth',
        'plugin.RADIUS.Radacct',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = $this->getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Users);

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
