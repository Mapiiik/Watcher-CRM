<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Radius\Model\Table\RadgroupreplyTable;

/**
 * Radius\Model\Table\RadgroupreplyTable Test Case
 */
class RadgroupreplyTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Radius\Model\Table\RadgroupreplyTable
     */
    protected $Radgroupreply;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radgroupreply',
        'plugin.Radius.Radusergroup',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Radius.Radgroupreply') ? [] : ['className' => RadgroupreplyTable::class];
        $this->Radgroupreply = $this->getTableLocator()->get('Radius.Radgroupreply', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Radgroupreply);

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
