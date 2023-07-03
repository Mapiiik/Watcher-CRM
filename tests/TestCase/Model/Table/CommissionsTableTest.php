<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\CommissionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\CommissionsTable Test Case
 */
class CommissionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\CommissionsTable
     */
    protected $CommissionsTable;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Commissions',
        'app.DealerCommissions',
        'app.Contracts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Commissions') ? [] : ['className' => CommissionsTable::class];
        $this->CommissionsTable = $this->fetchTable('Commissions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->CommissionsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\CommissionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
