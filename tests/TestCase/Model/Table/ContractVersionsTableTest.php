<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ContractVersionsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ContractVersionsTable Test Case
 */
class ContractVersionsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractVersionsTable
     */
    protected $ContractVersions;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ContractVersions',
        'app.Contracts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ContractVersions') ? [] : ['className' => ContractVersionsTable::class];
        $this->ContractVersions = $this->getTableLocator()->get('ContractVersions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ContractVersions);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ContractVersionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\ContractVersionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
