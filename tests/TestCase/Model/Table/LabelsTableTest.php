<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LabelsTable;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\LabelsTable Test Case
 */
class LabelsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\LabelsTable
     */
    protected $Labels;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.TaxRates',
        'app.Customers',
        'app.Labels',
        'app.CustomerLabels',
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
        $config = $this->getTableLocator()->exists('Labels') ? [] : ['className' => LabelsTable::class];
        $this->Labels = $this->getTableLocator()->get('Labels', $config);
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
        unset($this->Labels);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\LabelsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\LabelsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
