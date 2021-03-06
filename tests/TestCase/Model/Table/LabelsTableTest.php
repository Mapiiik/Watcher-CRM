<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LabelsTable;
use Cake\TestSuite\TestCase;

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
    protected $LabelsTable;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Labels') ? [] : ['className' => LabelsTable::class];
        $this->LabelsTable = $this->fetchTable('Labels', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->LabelsTable);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\LabelsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\LabelsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
