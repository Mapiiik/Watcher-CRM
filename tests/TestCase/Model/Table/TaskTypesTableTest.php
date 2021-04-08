<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TaskTypesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TaskTypesTable Test Case
 */
class TaskTypesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TaskTypesTable
     */
    protected $TaskTypes;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.TaskTypes',
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
        $config = $this->getTableLocator()->exists('TaskTypes') ? [] : ['className' => TaskTypesTable::class];
        $this->TaskTypes = $this->getTableLocator()->get('TaskTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TaskTypes);

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
