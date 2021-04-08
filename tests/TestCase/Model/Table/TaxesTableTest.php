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
    protected $Taxes;

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
        $this->Taxes = $this->getTableLocator()->get('Taxes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Taxes);

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
