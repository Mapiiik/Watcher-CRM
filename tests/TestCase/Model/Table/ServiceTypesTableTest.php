<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ServiceTypesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ServiceTypesTable Test Case
 */
class ServiceTypesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ServiceTypesTable
     */
    protected $ServiceTypesTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.ServiceTypes',
        'app.Contracts',
        'app.Queues',
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
        $config = $this->getTableLocator()->exists('ServiceTypes') ? [] : ['className' => ServiceTypesTable::class];
        $this->ServiceTypesTable = $this->getTableLocator()->get('ServiceTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ServiceTypesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ServiceTypesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
