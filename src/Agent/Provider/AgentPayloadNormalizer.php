<?php
declare(strict_types=1);

namespace App\Agent\Provider;

use App\Agent\Dto\AccessListChanges;
use App\Agent\Dto\AccessListEntry;
use App\Agent\Dto\AddressListChanges;
use App\Agent\Dto\AddressListEntry;
use App\Agent\Dto\DisconnectResult;
use App\Agent\Dto\PingResult;

/**
 * What the agent answers with, turned into results.
 *
 * The agent runs beside the network rather than beside this application and is deployed on its own
 * schedule, so a field that is missing or of a type nobody expected is read as not being there. The
 * verdict is the exception: an answer with no verdict in it is not a verdict of no, it is not an
 * answer to this question at all, and the client turns it away before it reaches here.
 */
final class AgentPayloadNormalizer
{
    /**
     * What came of a ping.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Agent\Dto\PingResult
     */
    public static function ping(array $body): PingResult
    {
        /** @var array<string, mixed> $body */
        return new PingResult(
            reachable: (bool)($body['reachable'] ?? false),
            lossPercent: self::floatOrNull($body['loss_pct'] ?? null),
            message: self::stringOrNull($body['message'] ?? null),
            raw: $body,
        );
    }

    /**
     * What came of a disconnect.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Agent\Dto\DisconnectResult
     */
    public static function disconnect(array $body): DisconnectResult
    {
        $causes = $body['error_causes'] ?? [];
        $causes = is_array($causes) ? $causes : [];

        /** @var array<string, mixed> $body */
        return new DisconnectResult(
            success: (bool)($body['success'] ?? false),
            result: self::stringOrNull($body['result'] ?? null),
            message: self::stringOrNull($body['message'] ?? null),
            errorCauses: array_values(array_map(intval(...), array_filter($causes, is_numeric(...)))),
            raw: $body,
        );
    }

    /**
     * What came of changing a firewall address list on a router.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Agent\Dto\AddressListChanges
     */
    public static function addressList(array $body): AddressListChanges
    {
        $unchanged = $body['unchanged'] ?? 0;

        /** @var array<string, mixed> $body */
        return new AddressListChanges(
            host: self::stringOrNull($body['host'] ?? null),
            transport: self::stringOrNull($body['transport'] ?? null),
            added: self::addressListEntries($body['added'] ?? null),
            updated: self::addressListEntries($body['updated'] ?? null),
            removed: self::addressListEntries($body['removed'] ?? null),
            unchanged: is_numeric($unchanged) ? (int)$unchanged : 0,
            raw: $body,
        );
    }

    /**
     * What came of taking a station off the access lists of an access point.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Agent\Dto\AccessListChanges
     */
    public static function accessList(array $body): AccessListChanges
    {
        $removed = [];
        foreach (self::rows($body['removed'] ?? null) as $row) {
            $macAddress = self::stringOrNull($row['mac_address'] ?? null);
            if ($macAddress === null) {
                continue;
            }

            $removed[] = new AccessListEntry(
                menu: self::stringOrNull($row['menu'] ?? null) ?? '',
                interface: self::stringOrNull($row['interface'] ?? null) ?? '',
                macAddress: $macAddress,
            );
        }

        /** @var array<string, mixed> $body */
        return new AccessListChanges(
            host: self::stringOrNull($body['host'] ?? null),
            transport: self::stringOrNull($body['transport'] ?? null),
            removed: $removed,
            raw: $body,
        );
    }

    /**
     * Entries of an address list, leaving out any without an address, which says nothing.
     *
     * @param mixed $value Value to read.
     * @return list<\App\Agent\Dto\AddressListEntry>
     */
    private static function addressListEntries(mixed $value): array
    {
        $entries = [];
        foreach (self::rows($value) as $row) {
            $address = self::stringOrNull($row['address'] ?? null);
            if ($address === null) {
                continue;
            }

            $entries[] = new AddressListEntry(
                address: $address,
                family: self::stringOrNull($row['family'] ?? null) ?? (str_contains($address, ':') ? 'ipv6' : 'ipv4'),
                comment: self::stringOrNull($row['comment'] ?? null) ?? '',
            );
        }

        return $entries;
    }

    /**
     * The objects of a list, leaving out whatever is not one.
     *
     * @param mixed $value Value to read.
     * @return list<array<mixed>>
     */
    private static function rows(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_array(...))) : [];
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
