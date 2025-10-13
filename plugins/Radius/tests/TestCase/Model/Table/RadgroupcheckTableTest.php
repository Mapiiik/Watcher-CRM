<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Override;
use Radius\Model\Table\RadgroupcheckTable;

/**
 * Radius\Model\Table\RadgroupcheckTable Test Case
 */
class RadgroupcheckTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Radius\Model\Table\RadgroupcheckTable
     */
    protected $Radgroupcheck;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radgroupcheck',
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
        $config = $this->getTableLocator()->exists('Radius.Radgroupcheck') ? [] : ['className' => RadgroupcheckTable::class];
        $this->Radgroupcheck = $this->getTableLocator()->get('Radius.Radgroupcheck', $config);
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
        unset($this->Radgroupcheck);

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
