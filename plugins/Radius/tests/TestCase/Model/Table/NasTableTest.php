<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Radius\Model\Table\NasTable;

/**
 * Radius\Model\Table\NasTable Test Case
 */
class NasTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Radius\Model\Table\NasTable
     */
    protected $Nas;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.Radius.Nas',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Nas') ? [] : ['className' => NasTable::class];
        $this->Nas = $this->fetchTable('Nas', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Nas);

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
     * Test defaultConnectionName method
     *
     * @return void
     */
    public function testDefaultConnectionName(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
