<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LoginsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\LoginsTable Test Case
 */
class LoginsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\LoginsTable
     */
    protected $LoginsTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Logins',
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
        $config = $this->getTableLocator()->exists('Logins') ? [] : ['className' => LoginsTable::class];
        $this->LoginsTable = $this->getTableLocator()->get('Logins', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->LoginsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\LoginsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\LoginsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
