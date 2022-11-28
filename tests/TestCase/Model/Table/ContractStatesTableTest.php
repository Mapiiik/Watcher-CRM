<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ContractStatesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ContractStatesTable Test Case
 */
class ContractStatesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractStatesTable
     */
    protected $ContractStates;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ContractStates',
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
        $config = $this->getTableLocator()->exists('ContractStates') ? [] : ['className' => ContractStatesTable::class];
        $this->ContractStates = $this->getTableLocator()->get('ContractStates', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ContractStates);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\ContractStatesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
