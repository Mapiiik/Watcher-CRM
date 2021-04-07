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
    protected $Brokerages;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Brokerages',
        'app.BrokerageDealers',
        'app.Contracts',
        'app.Ips',
        'app.RemovedIps',
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
        $this->Brokerages = $this->getTableLocator()->get('Brokerages', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Brokerages);

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
