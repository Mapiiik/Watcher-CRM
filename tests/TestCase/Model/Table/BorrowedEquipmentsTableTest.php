<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BorrowedEquipmentsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BorrowedEquipmentsTable Test Case
 */
class BorrowedEquipmentsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BorrowedEquipmentsTable
     */
    protected $BorrowedEquipments;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.BorrowedEquipments',
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
        $config = $this->getTableLocator()->exists('BorrowedEquipments') ? [] : ['className' => BorrowedEquipmentsTable::class];
        $this->BorrowedEquipments = $this->getTableLocator()->get('BorrowedEquipments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BorrowedEquipments);

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
