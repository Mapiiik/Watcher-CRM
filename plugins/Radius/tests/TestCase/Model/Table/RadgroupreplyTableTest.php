<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Override;
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
    #[Override]
    protected function setUp(): void
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
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
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
     * The table lives in the radius server's own database rather than ours, so it has to name that
     * connection - on the default one the table is simply not there.
     *
     * @return void
     * @link \Radius\Model\Table\RadgroupreplyTable::defaultConnectionName()
     */
    public function testDefaultConnectionName(): void
    {
        $this->assertSame('radius', RadgroupreplyTable::defaultConnectionName());
        // the test environment aliases it onto the radius test database, so what the connection
        // is called there is not 'radius' itself - but it is still a radius one, never the default
        $this->assertStringContainsString('radius', $this->Radgroupreply->getConnection()->configName());
    }
}
