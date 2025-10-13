<?php
declare(strict_types=1);

namespace Ruian\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Ruian\Model\Table\AddressesTable;

/**
 * Ruian\Model\Table\AddressesTable Test Case
 */
class AddressesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Ruian\Model\Table\AddressesTable
     */
    protected $Addresses;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Ruian.Addresses',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Ruian.Addresses') ? [] : ['className' => AddressesTable::class];
        $this->Addresses = $this->getTableLocator()->get('Ruian.Addresses', $config);
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
