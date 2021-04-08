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
    protected $EquipmentTypes;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
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
        $this->EquipmentTypes = $this->getTableLocator()->get('EquipmentTypes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->EquipmentTypes);

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
