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
    protected $Logins;

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
        $this->Logins = $this->getTableLocator()->get('Logins', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Logins);

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
}
