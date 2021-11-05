<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TaxesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TaxesTable Test Case
 */
class TaxesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TaxesTable
     */
    protected $TaxesTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Taxes',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Taxes') ? [] : ['className' => TaxesTable::class];
        $this->TaxesTable = $this->getTableLocator()->get('Taxes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TaxesTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\TaxesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
