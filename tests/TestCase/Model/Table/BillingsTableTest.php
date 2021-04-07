<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\BillingsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\BillingsTable Test Case
 */
class BillingsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\BillingsTable
     */
    protected $Billings;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Billings',
        'app.Customers',
        'app.Services',
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
        $config = $this->getTableLocator()->exists('Billings') ? [] : ['className' => BillingsTable::class];
        $this->Billings = $this->getTableLocator()->get('Billings', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Billings);

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
