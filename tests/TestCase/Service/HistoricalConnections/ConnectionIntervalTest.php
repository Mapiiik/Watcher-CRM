<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\HistoricalConnections;

use App\Model\Enum\HistoricalConnectionSource;
use App\Service\HistoricalConnections\ConnectionInterval;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\HistoricalConnections\ConnectionInterval Test Case
 *
 * The point key is what decides whether a reading extends the period already recorded or opens a new
 * one, so what belongs in it and what does not is the whole of the merging rule. Anything left out
 * of it can change during a period without splitting it; anything put into it splits every period it
 * changes during.
 */
#[UsesClass(ConnectionInterval::class)]
class ConnectionIntervalTest extends TestCase
{
    /**
     * An interval as a source hands it over, to be varied by the tests.
     *
     * @param array<string, mixed> $overrides Constructor arguments to replace.
     * @return \App\Service\HistoricalConnections\ConnectionInterval
     */
    private static function interval(array $overrides = []): ConnectionInterval
    {
        $arguments = $overrides + [
            'source' => HistoricalConnectionSource::Radius,
            'sourceReference' => 'account-1',
            'firstSeen' => new DateTime('2026-08-01 10:00:00'),
            'lastSeen' => new DateTime('2026-08-01 12:00:00'),
            'accountId' => 'e8c0a3d6-0000-4000-8000-000000000001',
            'customerId' => 'e8c0a3d6-0000-4000-8000-000000000002',
            'contractId' => 'e8c0a3d6-0000-4000-8000-000000000003',
            'stationId' => '00:11:22:33:44:55',
            'calledStationId' => '66:77:88:99:aa:bb',
            'nasIpAddress' => '10.20.30.40',
            'nasPortId' => 'ether1',
            'ipAddress' => '10.99.0.1',
            'ipv6Prefix' => '2001:db8::/56',
            'accountModified' => new DateTime('2026-08-01 09:00:00'),
        ];

        return new ConnectionInterval(...$arguments);
    }

    /**
     * The key names the place the connection was made at, together with whose it was.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\ConnectionInterval::pointKey()
     */
    public function testTheKeyNamesThePlaceAndWhoseItWas(): void
    {
        $this->assertSame(
            [
                'station_id' => '00:11:22:33:44:55',
                'nas_ip_address' => '10.20.30.40',
                'nas_port_id' => 'ether1',
                'ip_address' => '10.99.0.1',
                'ipv6_prefix' => '2001:db8::/56',
                'customer_id' => 'e8c0a3d6-0000-4000-8000-000000000002',
                'contract_id' => 'e8c0a3d6-0000-4000-8000-000000000003',
            ],
            self::interval()->pointKey(),
        );
    }

    /**
     * Two readings of the same place share a key even when the description around them differs -
     * which is what leaves the history alone when a routerboard is swapped during a service call.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\ConnectionInterval::pointKey()
     */
    public function testTheDescriptionAroundThePlaceDoesNotSplitAPeriod(): void
    {
        $this->assertSame(
            self::interval()->pointKey(),
            self::interval([
                'calledStationId' => 'cc:dd:ee:ff:00:11',
                'accountModified' => new DateTime('2026-08-02 09:00:00'),
                'lastSeen' => new DateTime('2026-08-01 18:00:00'),
            ])->pointKey(),
        );
    }

    /**
     * Where the account sits is part of the key, so moving it to another contract closes the period
     * that was running and opens a new one. Without that the move leaves no trace outside the audit
     * log.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\ConnectionInterval::pointKey()
     */
    public function testMovingTheAccountSplitsThePeriod(): void
    {
        $this->assertNotSame(
            self::interval()->pointKey(),
            self::interval(['contractId' => 'e8c0a3d6-0000-4000-8000-00000000000f'])->pointKey(),
        );
    }

    /**
     * Restating an interval under another placement changes the placement and nothing else - the
     * reading itself is what a source observed and is not the updater's to rewrite.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\ConnectionInterval::withPlacement()
     */
    public function testRestatingAnIntervalChangesThePlacementAndNothingElse(): void
    {
        $original = self::interval();

        $restated = $original->withPlacement(
            'e8c0a3d6-0000-4000-8000-00000000000a',
            'e8c0a3d6-0000-4000-8000-00000000000b',
        );

        $this->assertSame('e8c0a3d6-0000-4000-8000-00000000000a', $restated->customerId);
        $this->assertSame('e8c0a3d6-0000-4000-8000-00000000000b', $restated->contractId);

        $this->assertSame($original->source, $restated->source);
        $this->assertSame($original->sourceReference, $restated->sourceReference);
        $this->assertSame($original->firstSeen, $restated->firstSeen);
        $this->assertSame($original->lastSeen, $restated->lastSeen);
        $this->assertSame($original->accountId, $restated->accountId);
        $this->assertSame($original->stationId, $restated->stationId);
        $this->assertSame($original->calledStationId, $restated->calledStationId);
        $this->assertSame($original->nasIpAddress, $restated->nasIpAddress);
        $this->assertSame($original->nasPortId, $restated->nasPortId);
        $this->assertSame($original->ipAddress, $restated->ipAddress);
        $this->assertSame($original->ipv6Prefix, $restated->ipv6Prefix);
        $this->assertSame($original->accountModified, $restated->accountModified);
    }

    /**
     * Restating leaves the interval it was asked about untouched, so an updater holding on to the
     * original still has what the source said.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\ConnectionInterval::withPlacement()
     */
    public function testRestatingLeavesTheOriginalUntouched(): void
    {
        $original = self::interval();

        $original->withPlacement(null, null);

        $this->assertSame('e8c0a3d6-0000-4000-8000-000000000002', $original->customerId);
        $this->assertSame('e8c0a3d6-0000-4000-8000-000000000003', $original->contractId);
    }

    /**
     * An interval belonging to nobody in particular is a shape the key takes rather than something
     * to be filled in - a reading from a source that does not know whose the account is still has to
     * merge with the next one like it.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\ConnectionInterval::pointKey()
     */
    public function testAnIntervalBelongingToNobodyStillHasAKey(): void
    {
        $key = self::interval(['customerId' => null, 'contractId' => null])->pointKey();

        $this->assertNull($key['customer_id']);
        $this->assertNull($key['contract_id']);
    }
}
