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
     * keyed by "source|registry_ref". Suitable when the caller needs the
     * complete authoritative data (GPS, city, street, number, raw fields),
     * not just the formatted label.
     *
     * Entities without both `address_registry_source` and
     * `address_registry_reference` are skipped. Duplicate references are
     * de‑duplicated before the API call. Items unknown to the registry are
     * silently absent from the result.
     *
     * @param iterable<\App\Model\Entity\Address> $addresses
     * @return array<string, array<string, mixed>>  Map: "source|registry_ref" => match object
     * @throws \RuntimeException  On transport/API errors (bubbled from ApiClient)
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
                fn(array $match): string => $match['source'] . '|' . $match['registry_ref'],
            );

        return $return->toArray();
    }

    /**
     * Build a ["source|registry_ref" => formatted_address] map from a set of CRM
     * address entities, suitable for select-dropdowns and filter lists.
     *
     * Entities without both `address_registry_source` and
     * `address_registry_reference` are skipped. Duplicate references (multiple
     * CRM entities pointing to the same registry entry) are de‑duplicated
     * before the API call. Items unknown to the registry are silently omitted
     * from the result.
     *
     * Sort order: city → street → house number (natural numeric ordering),
     * producing a stable, human-friendly dropdown list.
     *
     * @param iterable<\App\Model\Entity\Address> $addresses
     * @return array<string, string>  Map: "source|registry_ref" => formatted_address
     * @throws \RuntimeException  On transport/API errors (bubbled from ApiClient)
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
                fn(array $match): string => ($match['city'] ?? '')
                    . '|' . ($match['street'] ?? '')
                    . '|' . str_pad((string)($match['house_number'] ?? ''), 8, '0', STR_PAD_LEFT),
                SORT_ASC,
                SORT_NATURAL,
            )
            ->combine(
                fn(array $match): string => $match['source'] . '|' . $match['registry_ref'],
                'formatted_address',
            );

        return $return->toArray();
    }

    /**
     * Extract (source, registry_id) pairs from a set of CRM address entities
     * and de‑duplicate them. Only entities that have both
     * `address_registry_source` and `address_registry_reference` defined are
     * included.
     *
     * The returned list is suitable as input for ApiClient::byIdBatch() or
     * byIdBatchFromCache(). Duplicate references (multiple CRM entities
     * pointing to the same registry entry) are collapsed into a single item
     * to avoid redundant API calls.
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
            if ($source === null) {
                continue;
            }
            if ($reference === null) {
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
