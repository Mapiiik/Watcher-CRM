<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RouterContactsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RouterContactsTable Test Case
 */
class RouterContactsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RouterContactsTable
     */
    protected $RouterContacts;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.RouterContacts',
        'app.Routers',
        'app.Customers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('RouterContacts') ? [] : ['className' => RouterContactsTable::class];
        $this->RouterContacts = $this->getTableLocator()->get('RouterContacts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->RouterContacts);

        parent::tearDown();
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
