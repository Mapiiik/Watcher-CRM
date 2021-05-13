<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RoutersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RoutersTable Test Case
 */
class RoutersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RoutersTable
     */
    protected $Routers;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Routers',
        'app.Ranges',
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
        $config = $this->getTableLocator()->exists('Routers') ? [] : ['className' => RoutersTable::class];
        $this->Routers = $this->getTableLocator()->get('Routers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Routers);

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
