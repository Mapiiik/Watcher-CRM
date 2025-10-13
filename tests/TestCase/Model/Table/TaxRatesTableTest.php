<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TaxRatesTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\TaxRatesTable Test Case
 */
class TaxRatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TaxRatesTable
     */
    protected $TaxRates;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.TaxRates',
        'app.Customers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TaxRates') ? [] : ['className' => TaxRatesTable::class];
        $this->TaxRates = $this->getTableLocator()->get('TaxRates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->TaxRates);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\TaxRatesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\TaxRatesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
