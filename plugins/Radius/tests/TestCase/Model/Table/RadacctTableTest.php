<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Model\Table;

use App\Test\Traits\TableTestTrait;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;
use Override;
use Radius\Model\Table\RadacctTable;

/**
 * Radius\Model\Table\RadacctTable Test Case
 */
class RadacctTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \Radius\Model\Table\RadacctTable
     */
    protected $Radacct;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radacct',
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
        $config = $this->getTableLocator()->exists('Radius.Radacct') ? [] : ['className' => RadacctTable::class];
        $this->Radacct = $this->getTableLocator()->get('Radius.Radacct', $config);
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
        unset($this->Radacct);

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
        $this->assertEmptyRecordIsRefused($this->Radacct);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->Radacct);
    }

    /**
     * A session is counted in the share of it that falls in the period, an open one up to its last
     * update, and one outside the period not at all.
     *
     * @return void
     * @link \Radius\Model\Table\RadacctTable::octetsByContract()
     */
    public function testASessionCountsForTheShareOfItInThePeriod(): void
    {
        $username = $this->getTableLocator()->get('Radius.Accounts')->find()->firstOrFail()->get('username');
        $connection = $this->Radacct->getConnection();
        $session = function (int $id, string $start, ?string $update, ?string $stop, int $octets) use ($connection, $username): void {
            $connection->insert('radacct', [
                'radacctid' => $id,
                'acctsessionid' => 's' . $id,
                'acctuniqueid' => 'u' . $id,
                'username' => $username,
                'nasipaddress' => '10.10.10.1',
                'acctstarttime' => $start,
                'acctupdatetime' => $update,
                'acctstoptime' => $stop,
                'acctinputoctets' => $octets,
                'acctoutputoctets' => $octets,
            ]);
        };
        // wholly inside
        $session(11, '2026-04-10 00:00:00+00', null, '2026-04-11 00:00:00+00', 500);
        // half of it inside
        $session(12, '2026-03-31 00:00:00+00', null, '2026-04-02 00:00:00+00', 1000);
        // open, last heard of inside, a quarter of it inside
        $session(13, '2026-03-29 00:00:00+00', '2026-04-02 00:00:00+00', null, 2000);
        // after the period
        $session(14, '2026-07-02 00:00:00+00', null, '2026-07-03 00:00:00+00', 9999);

        $octets = $this->Radacct->octetsByContract(
            new DateTimeImmutable('2026-04-01 00:00:00+00'),
            new DateTimeImmutable('2026-07-01 00:00:00+00'),
        );

        $this->assertEqualsWithDelta(1000 + 1000 + 1000, array_sum($octets), 0.01);
    }

    /**
     * The table lives in the radius server's own database rather than ours, so it has to name that
     * connection - on the default one the table is simply not there.
     *
     * @return void
     * @link \Radius\Model\Table\RadacctTable::defaultConnectionName()
     */
    public function testDefaultConnectionName(): void
    {
        $this->assertSame('radius', RadacctTable::defaultConnectionName());
        // the test environment aliases it onto the radius test database, so what the connection
        // is called there is not 'radius' itself - but it is still a radius one, never the default
        $this->assertStringContainsString('radius', $this->Radacct->getConnection()->configName());
    }
}
