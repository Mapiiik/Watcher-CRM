<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Radius\Model\Table\AccountsTable;

/**
 * Radius\Model\Table\AccountsTable Test Case
 */
class AccountsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Radius\Model\Table\AccountsTable
     */
    protected $Accounts;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Accounts',
        'plugin.Radius.Radcheck',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Radpostauth',
        'plugin.Radius.Radacct',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Radius.Accounts') ? [] : ['className' => AccountsTable::class];
        $this->Accounts = $this->getTableLocator()->get('Radius.Accounts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Accounts);

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
