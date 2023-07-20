<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Test\TestCase\Model\Table;

use BookkeepingPohoda\Model\Table\InvoicesTable;
use Cake\TestSuite\TestCase;

/**
 * BookkeepingPohoda\Model\Table\InvoicesTable Test Case
 */
class InvoicesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \BookkeepingPohoda\Model\Table\InvoicesTable
     */
    protected $Invoices;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.BookkeepingPohoda.Invoices',
        'plugin.BookkeepingPohoda.Customers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('BookkeepingPohoda.Invoices') ? [] : ['className' => InvoicesTable::class];
        $this->Invoices = $this->getTableLocator()->get('BookkeepingPohoda.Invoices', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Invoices);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \BookkeepingPohoda\Model\Table\InvoicesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \BookkeepingPohoda\Model\Table\InvoicesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
