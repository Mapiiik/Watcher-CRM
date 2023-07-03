<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ServicesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ServicesTable Test Case
 */
class ServicesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ServicesTable
     */
    protected $ServicesTable;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Services',
        'app.ServiceTypes',
        'app.Queues',
        'app.Billings',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Services') ? [] : ['className' => ServicesTable::class];
        $this->ServicesTable = $this->fetchTable('Services', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ServicesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ServicesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ServicesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
