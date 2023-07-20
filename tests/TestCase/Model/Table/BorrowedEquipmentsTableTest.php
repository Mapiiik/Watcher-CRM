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
    protected $BorrowedEquipmentsTable;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
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
        $this->BorrowedEquipmentsTable = $this->getTableLocator()->get('BorrowedEquipments', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->BorrowedEquipmentsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\BorrowedEquipmentsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\BorrowedEquipmentsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
