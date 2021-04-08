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
    protected $Labels;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Labels',
        'app.LabelCustomers',
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
        $this->Labels = $this->getTableLocator()->get('Labels', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Labels);

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

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
