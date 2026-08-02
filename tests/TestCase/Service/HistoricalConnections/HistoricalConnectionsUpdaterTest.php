<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\HistoricalConnections;

use App\Model\Enum\FirstSeenSource;
use App\Model\Enum\HistoricalConnectionSource;
use App\Service\HistoricalConnections\ConnectionInterval;
use App\Service\HistoricalConnections\HistoricalConnectionsUpdater;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Service\HistoricalConnections\HistoricalConnectionsUpdater Test Case
 */
class HistoricalConnectionsUpdaterTest extends TestCase
{
    /**
     * Account the intervals belong to
     */
    private const ACCOUNT = 'tester';

    /**
     * Customers standing in for a placement of the account
     */
    private const CUSTOMER_A = '403bab0e-52cd-4a8e-83f8-43c2457d0481';
    private const CUSTOMER_B = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * Table under test
     *
     * @var \App\Model\Table\HistoricalConnectionsTable
     */
    protected $HistoricalConnections;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.HistoricalConnections',
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

        /** @var \App\Model\Table\HistoricalConnectionsTable $table */
        $table = $this->getTableLocator()->get('HistoricalConnections');
        $this->HistoricalConnections = $table;
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
        unset($this->HistoricalConnections);

        parent::tearDown();
    }

    /**
     * An account nobody has recorded yet gets everything the source can see,
     * with only the earliest interval marked as a lower bound.
     *
     * @return void
     */
    public function testInitialLoadMarksOnlyTheEarliestAsUncertain(): void
    {
        $updater = new HistoricalConnectionsUpdater();
        $summary = $updater->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-02 12:00:00'),
            $this->interval('10.0.0.2', '2026-02-01 10:00:00', '2026-02-01 12:00:00'),
        ])]);

        $this->assertSame(2, $summary->opened);

        $recorded = $this->recorded();
        $this->assertCount(2, $recorded);
        $this->assertSame(FirstSeenSource::InitialLoad, $recorded[0]->first_seen_source);
        $this->assertFalse($recorded[0]->first_seen_exact);
        $this->assertSame(FirstSeenSource::Session, $recorded[1]->first_seen_source);
        $this->assertTrue($recorded[1]->first_seen_exact);
    }

    /**
     * Going away and coming back to the same place has to read as two separate
     * stays, otherwise the gap in between disappears from the history.
     *
     * @return void
     */
    public function testReturningToTheSamePlaceOpensANewInterval(): void
    {
        $updater = new HistoricalConnectionsUpdater();
        $updater->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-02 12:00:00'),
            $this->interval('10.0.0.2', '2026-02-01 10:00:00', '2026-02-01 12:00:00'),
            $this->interval('10.0.0.1', '2026-03-01 10:00:00', '2026-03-01 12:00:00'),
        ])]);

        $recorded = $this->recorded();
        $this->assertCount(3, $recorded);
        $this->assertSame('10.0.0.1', $recorded[0]->nas_ip_address);
        $this->assertSame('10.0.0.2', $recorded[1]->nas_ip_address);
        $this->assertSame('10.0.0.1', $recorded[2]->nas_ip_address);
        $this->assertNotSame($recorded[0]->id, $recorded[2]->id);
    }

    /**
     * Running the update again over the same data must not change anything, so
     * that a missed run can simply be caught up on.
     *
     * @return void
     */
    public function testRunningTwiceChangesNothing(): void
    {
        $intervals = [
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-02 12:00:00'),
            $this->interval('10.0.0.2', '2026-02-01 10:00:00', '2026-02-01 12:00:00'),
        ];

        (new HistoricalConnectionsUpdater())->update([new StubSource($intervals)]);
        $before = $this->recorded();

        $summary = (new HistoricalConnectionsUpdater())->update([new StubSource($intervals)]);

        $this->assertSame(0, $summary->opened);
        $this->assertSame(0, $summary->extended);

        $after = $this->recorded();
        $this->assertCount(count($before), $after);
        $this->assertEquals($before[0]->first_seen, $after[0]->first_seen);
        $this->assertEquals($before[1]->last_seen, $after[1]->last_seen);
    }

    /**
     * Once the oldest sessions of a running stay have been purged, the source
     * reports it as having started later than it did. The recorded start is the
     * older evidence and has to win, with only the end moving forward.
     *
     * @return void
     */
    public function testAStayOutlivingItsOldestSessionsIsExtendedNotDuplicated(): void
    {
        (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-10 12:00:00'),
        ])]);

        // the same stay, seen again after its first days aged out of the source
        $summary = (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-05 10:00:00', '2026-02-20 12:00:00'),
        ])]);

        $this->assertSame(0, $summary->opened);
        $this->assertSame(1, $summary->extended);

        $recorded = $this->recorded();
        $this->assertCount(1, $recorded);
        $this->assertSame('2026-01-01 10:00:00', $recorded[0]->first_seen->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-20 12:00:00', $recorded[0]->last_seen->format('Y-m-d H:i:s'));
    }

    /**
     * A stay whose last session is still running ends later than the stay that
     * follows it began, so intervals reported by a source may overlap. Those
     * older ones are settled and must be left alone, not offered again on every
     * run for the unique index to reject.
     *
     * @return void
     */
    public function testAnOverlappingSettledIntervalIsNotOfferedAgain(): void
    {
        // the first stay is still open, so it outlasts the start of the second
        $intervals = [
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-06-01 12:00:00'),
            $this->interval('10.0.0.2', '2026-02-01 10:00:00', '2026-02-02 12:00:00'),
        ];

        (new HistoricalConnectionsUpdater())->update([new StubSource($intervals)]);
        $this->assertCount(2, $this->recorded());

        $updater = new HistoricalConnectionsUpdater();
        $summary = $updater->update([new StubSource($intervals)]);

        $this->assertSame(0, $summary->opened);
        $this->assertCount(2, $this->recorded());
        $this->assertSame([], $updater->Messages->getMessages());
    }

    /**
     * Moving the account to another customer closes the running interval and
     * opens a new one, so an operator can see when it happened without having
     * to reach the audit log.
     *
     * @return void
     */
    public function testMovingTheAccountOpensANewInterval(): void
    {
        (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-10 12:00:00'),
        ])]);

        $movedAt = new DateTime('2026-01-15 08:30:00');

        // the same place, only the account now sits under another customer
        $summary = (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval(
                '10.0.0.1',
                '2026-01-05 10:00:00',
                '2026-02-20 12:00:00',
                customerId: self::CUSTOMER_B,
                accountModified: $movedAt,
            ),
        ])]);

        $this->assertSame(1, $summary->openedByAccountChange);

        $recorded = $this->recorded();
        $this->assertCount(2, $recorded);

        $this->assertSame(self::CUSTOMER_A, $recorded[0]->customer_id);
        $this->assertSame('2026-01-10 12:00:00', $recorded[0]->last_seen->format('Y-m-d H:i:s'));

        $this->assertSame(self::CUSTOMER_B, $recorded[1]->customer_id);
        $this->assertSame(FirstSeenSource::AccountChange, $recorded[1]->first_seen_source);
        $this->assertSame('2026-01-15 08:30:00', $recorded[1]->first_seen->format('Y-m-d H:i:s'));
        // the place carried over, the customer did not physically move
        $this->assertSame('10.0.0.1', $recorded[1]->nas_ip_address);
        // and the sessions that followed the move extended it
        $this->assertSame('2026-02-20 12:00:00', $recorded[1]->last_seen->format('Y-m-d H:i:s'));
    }

    /**
     * An account moved without connecting since still has to show up under its
     * new placement, or the history would keep claiming the old one.
     *
     * @return void
     */
    public function testMovingAnAccountWithNoLaterSessionsStillOpensAnInterval(): void
    {
        (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-10 12:00:00'),
        ])]);

        $movedAt = new DateTime('2026-01-15 08:30:00');

        (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval(
                '10.0.0.1',
                '2026-01-01 10:00:00',
                '2026-01-10 12:00:00',
                customerId: self::CUSTOMER_B,
                accountModified: $movedAt,
            ),
        ])]);

        $recorded = $this->recorded();
        $this->assertCount(2, $recorded);
        $this->assertSame(self::CUSTOMER_B, $recorded[1]->customer_id);
        $this->assertEquals($recorded[1]->first_seen, $recorded[1]->last_seen);
    }

    /**
     * A source that cannot be reached reports nothing, which must never be read
     * as every account having gone away.
     *
     * @return void
     */
    public function testAnUnreachableSourceIsLeftAlone(): void
    {
        (new HistoricalConnectionsUpdater())->update([new StubSource([
            $this->interval('10.0.0.1', '2026-01-01 10:00:00', '2026-01-10 12:00:00'),
        ])]);

        $summary = (new HistoricalConnectionsUpdater())->update([new StubSource([], available: false)]);

        $this->assertSame(['radius'], $summary->unavailableSources);
        $this->assertSame(0, $summary->accounts);
        $this->assertCount(1, $this->recorded());
    }

    /**
     * Everything recorded, oldest first.
     *
     * @return array<\App\Model\Entity\HistoricalConnection>
     */
    private function recorded(): array
    {
        /** @var array<\App\Model\Entity\HistoricalConnection> $recorded */
        $recorded = $this->HistoricalConnections->find()
            ->orderBy(['first_seen' => 'ASC'])
            ->toArray();

        return $recorded;
    }

    /**
     * An interval at a given network access server.
     *
     * @param string $nasIpAddress Address of the network access server.
     * @param string $firstSeen Start of the interval.
     * @param string $lastSeen End of the interval.
     * @param string $customerId Customer the account sits under.
     * @param \Cake\I18n\DateTime|null $accountModified When the account was last edited.
     * @return \App\Service\HistoricalConnections\ConnectionInterval
     */
    private function interval(
        string $nasIpAddress,
        string $firstSeen,
        string $lastSeen,
        string $customerId = self::CUSTOMER_A,
        ?DateTime $accountModified = null,
    ): ConnectionInterval {
        return new ConnectionInterval(
            source: HistoricalConnectionSource::Radius,
            sourceReference: self::ACCOUNT,
            firstSeen: new DateTime($firstSeen),
            lastSeen: new DateTime($lastSeen),
            customerId: $customerId,
            stationId: 'aa:bb:cc:dd:ee:ff',
            nasIpAddress: $nasIpAddress,
            nasPortId: 'ether1',
            ipAddress: '192.0.2.10',
            accountModified: $accountModified,
        );
    }
}
