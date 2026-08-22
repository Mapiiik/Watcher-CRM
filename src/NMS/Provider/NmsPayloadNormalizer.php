<?php
declare(strict_types=1);

namespace App\NMS\Provider;

use App\NMS\Dto\AccessPoint;
use App\NMS\Dto\IpAddressRange;
use App\NMS\Dto\RouterosDevice;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

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
}
