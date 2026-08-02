<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use Radius\Model\Table\NasTable;

/**
 * Radius\Model\Table\NasTable Test Case
 */
class NasTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \Radius\Model\Table\NasTable
     */
    protected $Nas;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Nas',
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
        $config = $this->getTableLocator()->exists('Radius.Nas') ? [] : ['className' => NasTable::class];
        $this->Nas = $this->getTableLocator()->get('Radius.Nas', $config);
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
        unset($this->Nas);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->Nas);
    }

    /**
     * The table lives in the radius server's own database rather than ours, so it has to name that
     * connection - on the default one the table is simply not there.
     *
     * @return void
     * @link \Radius\Model\Table\NasTable::defaultConnectionName()
     */
    public function testDefaultConnectionName(): void
    {
        $this->assertSame('radius', NasTable::defaultConnectionName());
        // the test environment aliases it onto the radius test database, so what the connection
        // is called there is not 'radius' itself - but it is still a radius one, never the default
        $this->assertStringContainsString('radius', $this->Nas->getConnection()->configName());
    }
}
