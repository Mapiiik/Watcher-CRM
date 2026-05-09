<?php
declare(strict_types=1);

namespace App\Addresses;

/**
 * Higher-level helpers that bridge CRM-side address entities and the
 * geo-addresses-postgis API via ApiClient.
 *
 * ApiClient is the raw transport (HTTP / API vocabulary). Resolver speaks
 * the CRM's domain language — accepts \App\Model\Entity\Address-like
 * entities (or anything with `address_registry_source` +
 * `address_registry_reference` properties) and returns ready-to-render
 * shapes for views / filters.
 */
class Resolver
{
    /**
     * Resolve a set of CRM address entities to full registry match objects,
     * keyed by registry_ref. Useful when the caller needs more than just
     * the formatted label (e.g. authoritative GPS coordinates).
     *
     * Same selection / dedup / failure semantics as dropdownMap().
     *
     * @param iterable<\App\Model\Entity\Address> $addresses
     * @return array<string, array<string, mixed>>
     * @throws \RuntimeException
     */
    public static function matchMap(iterable $addresses): array
    {
        $items = self::extractItems($addresses);
        if ($items === []) {
            return [];
        }

        $response = ApiClient::byIdBatchFromCache($items);
        /** @var array<string, array<string, mixed>> $matches */
        $matches = $response['matches'];

        /** @var \Cake\Collection\CollectionInterface<string, mixed> $return */
        $return = collection($matches)
            ->indexBy(
                fn(array $match) => $match['source'] . '|' . $match['registry_ref'],
            );

        return $return->toArray();
    }

    /**
     * Build a [registry_ref => formatted_address] map from a set of CRM
     * address entities, suitable as a select-dropdown options list.
     *
     * Entities without both `address_registry_source` and
     * `address_registry_reference` are skipped. Duplicates (multiple
     * entities pointing to the same registry entry) are de-duped before
     * the API call. Items the registry doesn't know about are silently
     * absent from the result.
     *
     * Sort order: city → street → house number, with natural numeric
     * ordering ("Karlova 2" before "Karlova 10").
     *
     * @param iterable<\App\Model\Entity\Address> $addresses
     * @return array<string, string>
     * @throws \RuntimeException Bubbled from ApiClient on transport / API
     *     errors; callers typically catch + Flash warning + fall back to [].
     */
    public static function dropdownMap(iterable $addresses): array
    {
        $matches = self::matchMap($addresses);
        if ($matches === []) {
            return [];
        }

        /** @var \Cake\Collection\CollectionInterface<string, string> $return */
        $return = collection($matches)
            ->sortBy(
                fn(array $match) => ($match['city'] ?? '')
                    . '|' . ($match['street'] ?? '')
                    . '|' . str_pad((string)($match['house_number'] ?? ''), 8, '0', STR_PAD_LEFT),
                SORT_ASC,
                SORT_NATURAL,
            )
            ->combine(
                fn(array $match) => $match['source'] . '|' . $match['registry_ref'],
                'formatted_address',
            );

        return $return->toArray();
    }

    /**
     * Pull (source, registry_id) pairs from entities and dedupe.
     *
     * @param iterable<\App\Model\Entity\Address> $addresses
     * @return list<array{source: string, registry_id: string}>
     */
    private static function extractItems(iterable $addresses): array
    {
        $seen = [];
        $items = [];

        foreach ($addresses as $address) {
            $source = $address->address_registry_source ?? null;
            $reference = $address->address_registry_reference ?? null;

            if ($source === null || $reference === null) {
                continue;
            }

            $key = $source . '|' . $reference;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $items[] = [
                'source' => (string)$source,
                'registry_id' => (string)$reference,
            ];
        }

        return $items;
    }
}
