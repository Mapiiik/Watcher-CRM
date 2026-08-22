<?php
declare(strict_types=1);

namespace App\Addresses\Provider;

use App\Addresses\Dto\Address;
use App\Addresses\Dto\Batch;
use App\Addresses\Dto\Lookup;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

/**
 * What a registry answers with, turned into addresses.
 *
 * Written to be forgiving: two national registries answer through one API, they do not carry the
 * same fields, and neither promises this application anything - so a field that is missing, empty
 * or of a type nobody expected is read as not being there. What is not forgiven is an address
 * without the registry it came from and its number there, because that pair is the only handle
 * this application has on it.
 */
final class AddressPayloadNormalizer
{
    /**
     * The addresses of a listing.
     *
     * @param array<mixed> $entries The listing as it arrived.
     * @return \Cake\Collection\CollectionInterface<int, \App\Addresses\Dto\Address>
     */
    public static function addresses(array $entries): CollectionInterface
    {
        /** @var array<int, \App\Addresses\Dto\Address> $addresses */
        $addresses = [];

        foreach ($entries as $entry) {
            $address = is_array($entry) ? self::address($entry) : null;

            if ($address !== null) {
                $addresses[] = $address;
            }
        }

        return new Collection($addresses);
    }

    /**
     * One address.
     *
     * @param array<mixed> $entry The address as it arrived.
     * @return \App\Addresses\Dto\Address|null
     */
    public static function address(array $entry): ?Address
    {
        $source = self::stringOrNull($entry['source'] ?? null);
        $reference = self::stringOrNull($entry['registry_ref'] ?? $entry['registry_id'] ?? null);

        if ($source === null || $reference === null) {
            return null;
        }

        // GeoJSON names the coordinates the other way round from the way one is written.
        $coordinates = $entry['geometry']['coordinates'] ?? null;
        $coordinates = is_array($coordinates) ? $coordinates : [];

        /** @var array<string, mixed> $entry */
        return new Address(
            source: $source,
            registryReference: $reference,
            formattedAddress: self::stringOrNull($entry['formatted_address'] ?? null),
            street: self::stringOrNull($entry['street'] ?? null),
            houseNumber: self::stringOrNull($entry['house_number'] ?? null),
            city: self::stringOrNull($entry['city'] ?? null),
            postalCode: self::stringOrNull($entry['postal_code'] ?? null),
            numberType: self::stringOrNull($entry['number_type'] ?? null),
            latitude: self::floatOrNull($coordinates[1] ?? null),
            longitude: self::floatOrNull($coordinates[0] ?? null),
            distance: self::floatOrNull($entry['distance_m'] ?? null),
            score: self::floatOrNull($entry['score'] ?? null),
            raw: $entry,
        );
    }

    /**
     * What came of one written address being looked up.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Addresses\Dto\Lookup
     */
    public static function lookup(array $body): Lookup
    {
        $matches = $body['matches'] ?? [];
        $step = $body['fallback_step'] ?? null;

        /** @var array<string, mixed> $body */
        return new Lookup(
            matches: self::addresses(is_array($matches) ? $matches : []),
            ambiguous: (bool)($body['ambiguous'] ?? false),
            fallbackStep: is_numeric($step) ? (int)$step : null,
            raw: $body,
        );
    }

    /**
     * What came of a whole set being looked up at once.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return \App\Addresses\Dto\Batch
     */
    public static function batch(array $body): Batch
    {
        $matches = $body['matches'] ?? [];
        $notFound = $body['not_found'] ?? [];

        /** @var array<string, mixed> $body */
        /** @var list<array<string, mixed>> $notFound */
        $notFound = is_array($notFound) ? array_values($notFound) : [];

        return new Batch(
            matches: self::addresses(is_array($matches) ? $matches : []),
            notFound: $notFound,
            raw: $body,
        );
    }

    /**
     * The answers to a set of written addresses, one apiece and in the order they were asked.
     *
     * @param array<mixed> $body The answer as it arrived.
     * @return list<\App\Addresses\Dto\Lookup>
     */
    public static function lookups(array $body): array
    {
        $results = $body['results'] ?? null;

        if (!is_array($results)) {
            return [];
        }

        $lookups = [];

        foreach ($results as $result) {
            $lookups[] = self::lookup(is_array($result) ? $result : []);
        }

        return $lookups;
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
