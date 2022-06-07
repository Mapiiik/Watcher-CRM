<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BrokeragesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BrokeragesTable Test Case
 */
class BrokeragesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BrokeragesTable
     */
    protected $BrokeragesTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Brokerages',
        'app.BrokerageDealers',
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
        $config = $this->getTableLocator()->exists('Brokerages') ? [] : ['className' => BrokeragesTable::class];
        $this->BrokeragesTable = $this->fetchTable('Brokerages', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BrokeragesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\BrokeragesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
