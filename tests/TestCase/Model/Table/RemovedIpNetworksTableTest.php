<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RemovedIpNetworksTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\RemovedIpNetworksTable Test Case
 */
class RemovedIpNetworksTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RemovedIpNetworksTable
     */
    protected $RemovedIpNetworks;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.TaxRates',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.RemovedIpNetworks',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('RemovedIpNetworks') ? [] : ['className' => RemovedIpNetworksTable::class];
        $this->RemovedIpNetworks = $this->getTableLocator()->get('RemovedIpNetworks', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->RemovedIpNetworks);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\RemovedIpNetworksTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\RemovedIpNetworksTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
