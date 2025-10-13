<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SoldEquipmentsTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\SoldEquipmentsTable Test Case
 */
class SoldEquipmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\SoldEquipmentsTable
     */
    protected $SoldEquipments;

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
        'app.EquipmentTypes',
        'app.SoldEquipments',
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
        $config = $this->getTableLocator()->exists('SoldEquipments') ? [] : ['className' => SoldEquipmentsTable::class];
        $this->SoldEquipments = $this->getTableLocator()->get('SoldEquipments', $config);
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
        unset($this->SoldEquipments);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\SoldEquipmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\SoldEquipmentsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
