<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DealerCommissionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\DealerCommissionsTable Test Case
 */
class DealerCommissionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\DealerCommissionsTable
     */
    protected $DealerCommissions;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.TaxRates',
        'app.Customers',
        'app.Commissions',
        'app.DealerCommissions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('DealerCommissions') ? [] : ['className' => DealerCommissionsTable::class];
        $this->DealerCommissions = $this->getTableLocator()->get('DealerCommissions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->DealerCommissions);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\DealerCommissionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\DealerCommissionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
