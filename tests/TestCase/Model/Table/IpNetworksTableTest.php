<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\IpNetworksTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\IpNetworksTable Test Case
 */
class IpNetworksTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\IpNetworksTable
     */
    protected $IpNetworks;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.IpNetworks',
        'app.Customers',
        'app.Contracts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('IpNetworks') ? [] : ['className' => IpNetworksTable::class];
        $this->IpNetworks = $this->getTableLocator()->get('IpNetworks', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->IpNetworks);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\IpNetworksTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\IpNetworksTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
