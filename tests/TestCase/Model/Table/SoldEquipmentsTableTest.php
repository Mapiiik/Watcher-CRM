<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\SoldEquipmentsTable;
use Cake\TestSuite\TestCase;

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
    protected $SoldEquipmentsTable;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.SoldEquipments',
        'app.Customers',
        'app.Contracts',
        'app.EquipmentTypes',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('SoldEquipments') ? [] : ['className' => SoldEquipmentsTable::class];
        $this->SoldEquipmentsTable = $this->fetchTable('SoldEquipments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->SoldEquipmentsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\SoldEquipmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\SoldEquipmentsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
