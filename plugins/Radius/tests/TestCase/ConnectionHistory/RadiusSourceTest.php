<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\ConnectionHistory;

use App\Model\Enum\ConnectionHistorySource;
use App\Service\ConnectionHistory\ConnectionInterval;
use Cake\TestSuite\TestCase;
use Radius\ConnectionHistory\RadiusSource;

/**
 * Radius\ConnectionHistory\RadiusSource Test Case
 */
class RadiusSourceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.ConnectionHistoryAccounts',
        'plugin.Radius.ConnectionHistoryRadacct',
    ];

    /**
     * The source reports what it is, so intervals can be told apart later.
     *
     * @return void
     */
    public function testReportsItsSource(): void
    {
        $this->assertSame(ConnectionHistorySource::Radius, (new RadiusSource())->getSource());
    }

    /**
     * Consecutive sessions at one place collapse into a single interval, and a
     * return to a place already left starts a new one rather than reopening the
     * old. Without that the gap in between would vanish.
     *
     * @return void
     */
    public function testConsecutiveSessionsCollapseAndReturnsOpenANewInterval(): void
    {
        $intervals = $this->intervals();

        $this->assertCount(3, $intervals);

        $this->assertSame('10.0.0.1', $intervals[0]->nasIpAddress);
        $this->assertSame('2026-01-01 10:00:00', $intervals[0]->firstSeen->format('Y-m-d H:i:s'));
        $this->assertSame('2026-01-02 12:00:00', $intervals[0]->lastSeen->format('Y-m-d H:i:s'));

        $this->assertSame('10.0.0.2', $intervals[1]->nasIpAddress);

        // the same place as the first, but a separate stay
        $this->assertSame('10.0.0.1', $intervals[2]->nasIpAddress);
        $this->assertSame('2026-03-01 10:00:00', $intervals[2]->firstSeen->format('Y-m-d H:i:s'));
    }

    /**
     * A station reported in dashes and in colons is one station, not two, and
     * must not read as the customer having moved.
     *
     * @return void
     */
    public function testStationIdentifiersAreNormalised(): void
    {
        foreach ($this->intervals() as $interval) {
            $this->assertSame('aa:bb:cc:dd:ee:ff', $interval->stationId);
        }
    }

    /**
     * Accounts without IPv6 leave the prefix empty, which must not break the
     * comparison that decides where one stay ends and the next begins.
     *
     * @return void
     */
    public function testAMissingIpv6PrefixIsCarriedThroughAsNull(): void
    {
        $intervals = $this->intervals();

        $this->assertSame('2001:db8:1::/64', $intervals[0]->ipv6Prefix);
        $this->assertNull($intervals[1]->ipv6Prefix);
    }

    /**
     * A session still running has no stop time, so the interval ends at the last
     * sign of life instead.
     *
     * @return void
     */
    public function testARunningSessionEndsAtItsLastUpdate(): void
    {
        $intervals = $this->intervals();

        $this->assertSame('2026-03-02 14:00:00', $intervals[2]->lastSeen->format('Y-m-d H:i:s'));
    }

    /**
     * The placement of the account travels with the intervals, the accounting
     * data itself knows nothing about customers or contracts.
     *
     * @return void
     */
    public function testTheAccountPlacementTravelsWithTheIntervals(): void
    {
        $interval = $this->intervals()[0];

        $this->assertSame('tester', $interval->sourceReference);
        $this->assertSame('ab8f2c14-6d3e-4b91-9f0a-7c25d8e41b63', $interval->accountId);
        $this->assertSame('403bab0e-52cd-4a8e-83f8-43c2457d0481', $interval->customerId);
        $this->assertSame('7f76dc3f-a11b-4109-958b-4b0382545a66', $interval->contractId);
        $this->assertSame('2026-07-20 10:00:00', $interval->accountModified?->format('Y-m-d H:i:s'));
    }

    /**
     * Everything the source can derive, in order.
     *
     * @return array<\App\Service\ConnectionHistory\ConnectionInterval>
     */
    private function intervals(): array
    {
        $intervals = [];

        foreach ((new RadiusSource())->getIntervals() as $interval) {
            $this->assertInstanceOf(ConnectionInterval::class, $interval);
            $intervals[] = $interval;
        }

        return $intervals;
    }
}
