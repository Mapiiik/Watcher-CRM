<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BrokerageDealersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BrokerageDealersTable Test Case
 */
class BrokerageDealersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BrokerageDealersTable
     */
    protected $BrokerageDealersTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.BrokerageDealers',
        'app.Dealers',
        'app.Brokerages',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('BrokerageDealers') ? [] : ['className' => BrokerageDealersTable::class];
        $this->BrokerageDealersTable = $this->getTableLocator()->get('BrokerageDealers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BrokerageDealersTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\BrokerageDealersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\BrokerageDealersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
