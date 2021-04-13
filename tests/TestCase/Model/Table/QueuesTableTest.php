<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\QueuesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\QueuesTable Test Case
 */
class QueuesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\QueuesTable
     */
    protected $Queues;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Queues',
        'app.ServiceTypes',
        'app.Services',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Queues') ? [] : ['className' => QueuesTable::class];
        $this->Queues = $this->getTableLocator()->get('Queues', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Queues);

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
