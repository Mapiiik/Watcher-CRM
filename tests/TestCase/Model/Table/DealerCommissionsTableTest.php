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
    protected $DealerCommissionsTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.DealerCommissions',
        'app.Customers',
        'app.Commissions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('DealerCommissions') ? [] : ['className' => DealerCommissionsTable::class];
        $this->DealerCommissionsTable = $this->fetchTable('DealerCommissions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->DealerCommissionsTable);

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
