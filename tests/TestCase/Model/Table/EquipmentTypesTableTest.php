<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\EquipmentTypesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\EquipmentTypesTable Test Case
 */
class EquipmentTypesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\EquipmentTypesTable
     */
    protected $EquipmentTypesTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.SoldEquipments',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('EquipmentTypes') ? [] : ['className' => EquipmentTypesTable::class];
        $this->EquipmentTypesTable = $this->fetchTable('EquipmentTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->EquipmentTypesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\EquipmentTypesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
