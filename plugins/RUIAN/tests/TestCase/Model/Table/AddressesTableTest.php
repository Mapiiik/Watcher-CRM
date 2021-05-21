<?php
declare(strict_types=1);

namespace RUIAN\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RUIAN\Model\Table\AddressesTable;

/**
 * RUIAN\Model\Table\AddressesTable Test Case
 */
class AddressesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \RUIAN\Model\Table\AddressesTable
     */
    protected $Addresses;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.RUIAN.Addresses',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Addresses') ? [] : ['className' => AddressesTable::class];
        $this->Addresses = $this->getTableLocator()->get('Addresses', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Addresses);

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
