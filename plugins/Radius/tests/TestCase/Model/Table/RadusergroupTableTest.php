<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use Radius\Model\Table\RadusergroupTable;

/**
 * Radius\Model\Table\RadusergroupTable Test Case
 */
class RadusergroupTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \Radius\Model\Table\RadusergroupTable
     */
    protected $Radusergroup;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radgroupcheck',
        'plugin.Radius.Radgroupreply',
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
        $config = $this->getTableLocator()->exists('Radius.Radusergroup') ? [] : ['className' => RadusergroupTable::class];
        $this->Radusergroup = $this->getTableLocator()->get('Radius.Radusergroup', $config);
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
        unset($this->Radusergroup);

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
        $this->assertEmptyRecordIsRefused($this->Radusergroup);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Radusergroup);
    }

    /**
     * The table lives in the radius server's own database rather than ours, so it has to name that
     * connection - on the default one the table is simply not there.
     *
     * @return void
     * @link \Radius\Model\Table\RadusergroupTable::defaultConnectionName()
     */
    public function testDefaultConnectionName(): void
    {
        $this->assertSame('radius', RadusergroupTable::defaultConnectionName());
        // the test environment aliases it onto the radius test database, so what the connection
        // is called there is not 'radius' itself - but it is still a radius one, never the default
        $this->assertStringContainsString('radius', $this->Radusergroup->getConnection()->configName());
    }
}
