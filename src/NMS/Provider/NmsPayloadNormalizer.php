<?php
declare(strict_types=1);

namespace App\NMS\Provider;

use App\Model\Enum\OutageCertainty;
use App\NMS\Dto\AccessPoint;
use App\NMS\Dto\IpAddressRange;
use App\NMS\Dto\PowerOutage;
use App\NMS\Dto\RouterosDevice;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\I18n\DateTime;
use Throwable;

/**
 * What the network management system answers with, turned into things.
 *
 * Written to be forgiving. The other application is ours, but it is deployed on its own schedule
 * and grows fields of its own, so a field that is missing, empty or of a type nobody expected is
 * read as not being there rather than as a reason to stop. The one thing that is not forgiven is
 * an entry with no number of its own: nothing can be linked back to it, so it is passed over.
 */
final class NmsPayloadNormalizer
{
    /**
     * The access points of a listing.
     *
     * @param array<mixed> $entries The listing as it arrived.
     * @return \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\AccessPoint>
     */
    public static function accessPoints(array $entries): CollectionInterface
    {
        /** @var array<int, \App\NMS\Dto\AccessPoint> $accessPoints */
        $accessPoints = [];

        foreach ($entries as $entry) {
            $accessPoint = is_array($entry) ? self::accessPoint($entry) : null;

            if ($accessPoint !== null) {
                $accessPoints[] = $accessPoint;
            }
        }

        return new Collection($accessPoints);
    }

    /**
     * One access point.
     *
     * @param array<mixed> $entry The point as it arrived.
     * @return \App\NMS\Dto\AccessPoint|null
     */
    public static function accessPoint(array $entry): ?AccessPoint
    {
        $id = self::stringOrNull($entry['id'] ?? null);

        if ($id === null) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        return new AccessPoint(
            id: $id,
            name: self::stringOrNull($entry['name'] ?? null),
            archived: self::stringOrNull($entry['archived'] ?? null),
            parentAccessPointId: self::stringOrNull($entry['parent_access_point_id'] ?? null),
            // The other application names the axes the way a map does rather than the way a
            // coordinate is written, so `gps_y` is the latitude.
            latitude: self::floatOrNull($entry['gps_y'] ?? null),
            longitude: self::floatOrNull($entry['gps_x'] ?? null),
            raw: $entry,
        );
    }

    /**
     * The planned outages of a listing.
     *
     * @param array<mixed> $entries The listing as it arrived.
     * @return \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\PowerOutage>
     */
    public static function powerOutages(array $entries): CollectionInterface
    {
        /** @var array<int, \App\NMS\Dto\PowerOutage> $outages */
        $outages = [];

        foreach ($entries as $entry) {
            $outage = is_array($entry) ? self::powerOutage($entry) : null;

            if ($outage !== null) {
                $outages[] = $outage;
            }
        }

        return new Collection($outages);
    }

    /**
     * One planned outage.
     *
     * An entry naming no mast is passed over: an outage this application cannot tie to a place of
     * the network is an outage it can say nothing about.
     *
     * @param array<mixed> $entry The outage as it arrived.
     * @return \App\NMS\Dto\PowerOutage|null
     */
    public static function powerOutage(array $entry): ?PowerOutage
    {
        $accessPointId = self::stringOrNull($entry['access_point_id'] ?? null);

        if ($accessPointId === null) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        return new PowerOutage(
            accessPointId: $accessPointId,
            accessPointName: self::stringOrNull($entry['access_point_name'] ?? null),
            connections: self::intOrZero($entry['connections'] ?? null),
            // Read into a date rather than kept as the text it arrived as, so that a page draws
            // it the way it draws every other date here. Left as text it reads as the wire format
            // it is - `2026-09-15T09:00:00+02:00` in the middle of a card.
            beginsAt: self::dateTimeOrNull($entry['begins_at'] ?? null),
            endsAt: self::dateTimeOrNull($entry['ends_at'] ?? null),
            // `tryFrom`, not `from`: the other application is deployed on its own schedule, and a
            // word it starts using that this one has never heard of is not a reason to stop.
            certainty: OutageCertainty::tryFrom((string)self::stringOrNull($entry['certainty'] ?? null)),
            matchedBy: self::stringOrNull($entry['matched_by'] ?? null),
            matchNote: self::stringOrNull($entry['match_note'] ?? null),
            summary: self::stringOrNull($entry['summary'] ?? null),
            announcementUrl: self::stringOrNull($entry['announcement_url'] ?? null),
            raw: $entry,
        );
    }

    /**
     * The ranges of a listing.
     *
     * @param array<mixed> $entries The listing as it arrived.
     * @return \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>
     */
    public static function ipAddressRanges(array $entries): CollectionInterface
    {
        /** @var array<int, \App\NMS\Dto\IpAddressRange> $ranges */
        $ranges = [];

        foreach ($entries as $entry) {
            $range = is_array($entry) ? self::ipAddressRange($entry) : null;

            if ($range !== null) {
                $ranges[] = $range;
            }
        }

        return new Collection($ranges);
    }

    /**
     * One range.
     *
     * @param array<mixed> $entry The range as it arrived.
     * @return \App\NMS\Dto\IpAddressRange|null
     */
    public static function ipAddressRange(array $entry): ?IpAddressRange
    {
        $id = self::stringOrNull($entry['id'] ?? null);

        if ($id === null) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        return new IpAddressRange(
            id: $id,
            name: self::stringOrNull($entry['name'] ?? null),
            network: self::stringOrNull($entry['ip_network'] ?? null),
            gateway: self::stringOrNull($entry['ip_gateway'] ?? null),
            accessPointId: self::stringOrNull($entry['access_point_id'] ?? null),
            accessPoint: self::nestedAccessPoint($entry),
            raw: $entry,
        );
    }

    /**
     * The devices of a listing.
     *
     * @param array<mixed> $entries The listing as it arrived.
     * @return \Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>
     */
    public static function routerosDevices(array $entries): CollectionInterface
    {
        /** @var array<int, \App\NMS\Dto\RouterosDevice> $devices */
        $devices = [];

        foreach ($entries as $entry) {
            $device = is_array($entry) ? self::routerosDevice($entry) : null;

            if ($device !== null) {
                $devices[] = $device;
            }
        }

        return new Collection($devices);
    }

    /**
     * One device.
     *
     * @param array<mixed> $entry The device as it arrived.
     * @return \App\NMS\Dto\RouterosDevice|null
     */
    public static function routerosDevice(array $entry): ?RouterosDevice
    {
        $id = self::stringOrNull($entry['id'] ?? null);

        if ($id === null) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        return new RouterosDevice(
            id: $id,
            name: self::stringOrNull($entry['name'] ?? null),
            systemDescription: self::stringOrNull($entry['system_description'] ?? null),
            accessPointId: self::stringOrNull($entry['access_point_id'] ?? null),
            accessPoint: self::nestedAccessPoint($entry),
            raw: $entry,
        );
    }

    /**
     * The point an entry names inside itself, where the reading was asked to expand it.
     *
     * @param array<mixed> $entry The entry as it arrived.
     * @return \App\NMS\Dto\AccessPoint|null
     */
    private static function nestedAccessPoint(array $entry): ?AccessPoint
    {
        $nested = $entry['access_point'] ?? null;

        return is_array($nested) ? self::accessPoint($nested) : null;
    }

    /**
     * @param mixed $value Value to read.
     * @return string|null
     */
    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && trim((string)$value) !== '' ? trim((string)$value) : null;
    }

    /**
     * @param mixed $value Value to read.
     * @return float|null
     */
    private static function floatOrNull(mixed $value): ?float
    {
        return is_scalar($value) && is_numeric($value) ? (float)$value : null;
    }

    /**
     * A count, or nothing counted.
     *
     * Read as zero rather than as null where it is missing or nonsense: a number that is not there
     * has to draw as something, and "no connections known" and "none" are near enough the same
     * news beside an outage.
     *
     * @param mixed $value Value to read.
     * @return int
     */
    private static function intOrZero(mixed $value): int
    {
        return is_scalar($value) && is_numeric($value) ? max(0, (int)$value) : 0;
    }

    /**
     * A moment, or nothing where what arrived does not read as one.
     *
     * Forgiving like everything else here: the other application decides how it writes its dates,
     * and a spelling this one cannot parse is read as the field not being there rather than as an
     * exception in the middle of drawing a card.
     *
     * @param mixed $value Value to read.
     * @return \Cake\I18n\DateTime|null
     */
    private static function dateTimeOrNull(mixed $value): ?DateTime
    {
        $when = self::stringOrNull($value);

        if ($when === null) {
            return null;
        }

        try {
            return new DateTime($when);
        } catch (Throwable) {
            return null;
        }
    }
}
