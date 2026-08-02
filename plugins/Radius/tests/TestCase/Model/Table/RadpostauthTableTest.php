<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Override;
use Radius\Model\Table\RadpostauthTable;

/**
 * Radius\Model\Table\RadpostauthTable Test Case
 */
class RadpostauthTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Radius\Model\Table\RadpostauthTable
     */
    protected $Radpostauth;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radpostauth',
        'plugin.Radius.Accounts',
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
        $config = $this->getTableLocator()->exists('Radius.Radpostauth') ? [] : ['className' => RadpostauthTable::class];
        $this->Radpostauth = $this->getTableLocator()->get('Radius.Radpostauth', $config);
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
        unset($this->Radpostauth);

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

    /**
     * The table lives in the radius server's own database rather than ours, so it has to name that
     * connection - on the default one the table is simply not there.
     *
     * @return void
     * @link \Radius\Model\Table\RadpostauthTable::defaultConnectionName()
     */
    public function testDefaultConnectionName(): void
    {
        $this->assertSame('radius', RadpostauthTable::defaultConnectionName());
        // the test environment aliases it onto the radius test database, so what the connection
        // is called there is not 'radius' itself - but it is still a radius one, never the default
        $this->assertStringContainsString('radius', $this->Radpostauth->getConnection()->configName());
    }
}
