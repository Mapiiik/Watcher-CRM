<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\AppUsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\AppUsersTable Test Case
 */
class AppUsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\AppUsersTable
     */
    protected $AppUsers;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('AppUsers') ? [] : ['className' => AppUsersTable::class];
        $this->AppUsers = $this->getTableLocator()->get('AppUsers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->AppUsers);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\PaymentPurposesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
