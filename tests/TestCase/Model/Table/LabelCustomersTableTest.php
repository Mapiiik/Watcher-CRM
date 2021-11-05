<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LabelCustomersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\LabelCustomersTable Test Case
 */
class LabelCustomersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\LabelCustomersTable
     */
    protected $LabelCustomersTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.LabelCustomers',
        'app.Labels',
        'app.Customers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('LabelCustomers') ? [] : ['className' => LabelCustomersTable::class];
        $this->LabelCustomersTable = $this->getTableLocator()->get('LabelCustomers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->LabelCustomersTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\LabelCustomersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\LabelCustomersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
