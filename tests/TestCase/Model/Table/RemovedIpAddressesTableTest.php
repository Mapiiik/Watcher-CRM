<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\RemovedIpAddressesTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\RemovedIpAddressesTable Test Case
 */
class RemovedIpAddressesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\RemovedIpAddressesTable
     */
    protected $RemovedIpAddresses;

    /**
     * Fixtures
     *
     * @var array<string>
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
        'app.RemovedIpAddresses',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('RemovedIpAddresses') ? [] : ['className' => RemovedIpAddressesTable::class];
        $this->RemovedIpAddresses = $this->getTableLocator()->get('RemovedIpAddresses', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->RemovedIpAddresses);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\RemovedIpAddressesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\RemovedIpAddressesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
