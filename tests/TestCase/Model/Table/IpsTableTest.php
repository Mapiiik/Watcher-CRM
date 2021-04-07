<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\IpsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\IpsTable Test Case
 */
class IpsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\IpsTable
     */
    protected $Ips;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Ips',
        'app.Customers',
        'app.Queues',
        'app.Devices',
        'app.Dealers',
        'app.Brokerages',
        'app.Routers',
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
        $config = $this->getTableLocator()->exists('Ips') ? [] : ['className' => IpsTable::class];
        $this->Ips = $this->getTableLocator()->get('Ips', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Ips);

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
