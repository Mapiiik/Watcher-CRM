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
    protected $TaskTypesTable;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $this->TaskTypesTable = $this->getTableLocator()->get('TaskTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TaskTypesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\TaskTypesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
