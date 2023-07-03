<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TaskStatesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TaskStatesTable Test Case
 */
class TaskStatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TaskStatesTable
     */
    protected $TaskStatesTable;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.TaskStates',
        'app.Tasks',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TaskStates') ? [] : ['className' => TaskStatesTable::class];
        $this->TaskStatesTable = $this->fetchTable('TaskStates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TaskStatesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\TaskStatesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
