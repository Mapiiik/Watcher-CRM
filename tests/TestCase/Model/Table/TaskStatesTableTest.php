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
    protected $TaskStates;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
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
        $this->TaskStates = $this->getTableLocator()->get('TaskStates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TaskStates);

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
}
