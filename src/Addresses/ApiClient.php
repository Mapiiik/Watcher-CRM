<?php
declare(strict_types=1);

namespace App\Addresses;

use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use RuntimeException;
use Throwable;

class ApiClient
{
    /**
     * Build the configured Cake HTTP client.
     */
    private static function http(int $timeout = 30): Client
    {
        $headers = ['Accept' => 'application/json'];

        $apiKey = (string)env('ADDRESSES_API_KEY');
        if ($apiKey !== '') {
            $headers['X-API-Key'] = $apiKey;
        }

        return new Client([
            'headers' => $headers,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Resolve a relative API path against ADDRESSES_API_URL.
     */
    private static function url(string $path): string
    {
        $apiUrl = rtrim((string)env('ADDRESSES_API_URL'), '/');
        if ($apiUrl === '') {
            throw new RuntimeException(__('Addresses API is not configured.'));
        }

        return $apiUrl . '/' . ltrim($path, '/');
    }

    /**
     * GET request to the Addresses API.
     *
     * @param array<string, mixed> $query
     */
    private static function getRequest(string $path, array $query = [], int $timeout = 30): Response
    {
        return self::http($timeout)->get(self::url($path), $query);
    }

    /**
     * POST request (JSON body) to the Addresses API.
     *
     * @param array<string, mixed> $data
     */
    private static function postRequest(string $path, array $data = [], int $timeout = 30): Response
    {
        return self::http($timeout)->post(
            self::url($path),
            $data,
            ['type' => 'json'],
        );
    }

    /**
     * Validate the response and return the decoded JSON. Throws with the
     * server's `detail` field on non-2xx responses.
     *
     * @return array<int|string, mixed>
     */
    private static function decodeOrThrow(Response $response): array
    {
        $data = $response->getJson();

        if (!$response->isOk()) {
            throw new RuntimeException(
                __(
                    'Addresses API returned HTTP {0} ({1})',
                    $response->getStatusCode(),
                    self::extractError($data) ?? __('Unknown error'),
                ),
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(__('Addresses API returned an invalid response.'));
        }

        return $data;
    }

    /**
     * Extract a human-readable message from a FastAPI error body. FastAPI
     * uses {"detail": "..."} for plain errors and {"detail": [{"msg": ...}]}
     * for 422 validation errors.
     */
    private static function extractError(mixed $body): ?string
    {
        if (!is_array($body)) {
            return null;
        }

        $detail = $body['detail'] ?? null;

        if (is_string($detail)) {
            return $detail;
        }

        if (is_array($detail) && isset($detail[0]['msg']) && is_string($detail[0]['msg'])) {
            return $detail[0]['msg'];
        }

        return null;
    }

    /**
     * Liveness/readiness probe.
     *
     * @return array<string, mixed> { status: "ok"|"degraded", db: "up"|"down" }
     */
    public static function health(): array
    {
        try {
            $response = self::getRequest(path: 'v1/health', timeout: 5);
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var array<string, mixed> */
        return self::decodeOrThrow($response);
    }

    /**
     * Dataset metadata — row counts and last-refresh timestamps per table.
     *
     * @return array<string, mixed>
     */
    public static function meta(): array
    {
        try {
            $response = self::getRequest(path: 'v1/meta', timeout: 5);
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var array<string, mixed> */
        return self::decodeOrThrow($response);
    }

    /**
     * Cached variant of meta(). TTL is governed by the `addresses_api` cache config.
     *
     * @return array<string, mixed>
     */
    public static function metaFromCache(): array
    {
        return Cache::remember(
            'addresses_meta',
            fn() => self::meta(),
            'addresses_api',
        );
    }

    /**
     * Look up a single address by its registry id (kod_adm for CZ,
     * ogc_fid for HR). Returns null if the id is not present.
     *
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return array<string, mixed>|null
     */
    public static function byId(string $source, string $registryId, array $include = []): ?array
    {
        $query = $include !== [] ? ['include' => implode(',', $include)] : [];

        try {
            $response = self::getRequest(
                path: "v1/addresses/{$source}/{$registryId}",
                query: $query,
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        if ($response->getStatusCode() === 404) {
            return null;
        }

        /** @var array<string, mixed> */
        return self::decodeOrThrow($response);
    }

    /**
     * Cached variant of byId(). TTL is governed by the `addresses_api` cache config.
     *
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return array<string, mixed>|null
     */
    public static function byIdFromCache(string $source, string $registryId, array $include = []): ?array
    {
        // The key includes an `include` so that `?include=raw` does not share the cache with the default call.
        $key = sprintf(
            'address_%s_%s_%s',
            $source,
            $registryId,
            $include === [] ? 'normalized' : md5(implode(',', $include)),
        );

        return Cache::remember(
            $key,
            fn() => self::byId($source, $registryId, $include),
            'addresses_api',
        );
    }

    /**
     * Bulk by-id lookup. Returns all matches in arbitrary order plus the
     * list of input items that weren't found in the database. CZ + HR ids
     * can be mixed in one call.
     *
     * Each item must follow the API contract:
     *     ['source' => 'cz' | 'hr', 'registry_id' => string]
     *
     * @param array<int, array{source: string, registry_id: string}> $items
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return array<string, mixed> { matches: [...], not_found: [...] }
     * @throws \RuntimeException if the request fails or returns an error response
     */
    public static function byIdBatch(array $items, array $include = []): array
    {
        $path = 'v1/addresses/batch';
        if ($include !== []) {
            $path .= '?' . http_build_query(['include' => implode(',', $include)]);
        }

        try {
            $response = self::postRequest(
                path: $path,
                data: ['items' => $items],
                timeout: 60,
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var array<string, mixed> */
        return self::decodeOrThrow($response);
    }

    /**
     * Cached variant of byIdBatch. Best for stable input sets (e.g. "all
     * installation addresses in the CRM" rendered into a select box).
     *
     * The cache key is derived from a canonical (sorted) representation of
     * $items + $include, so two calls with the same set of ids in different
     * orderings hit the same entry. TTL is governed by the `addresses_api`
     * cache config.
     *
     * For one-off lookups prefer byIdBatch directly (or byId for single ids).
     *
     * @param array<int, array{source: string, registry_id: string}> $items
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return array<string, mixed> { matches: [...], not_found: [...] }
     */
    public static function byIdBatchFromCache(array $items, array $include = []): array
    {
        // Canonical form: sort by (source, registry_id) so equivalent input
        // sets — regardless of original ordering or duplicate entries — share
        // the same cache key.
        $canonical = $items;
        usort(
            $canonical,
            fn(array $a, array $b): int => $a['source'] <=> $b['source']
                    ?: $a['registry_id'] <=> $b['registry_id'],
        );

        $key = 'addresses_batch_' . md5(serialize([$canonical, $include]));

        return Cache::remember(
            $key,
            fn() => self::byIdBatch($items, $include),
            'addresses_api',
        );
    }

    /**
     * Structured address lookup. CZ runs a 5-variant fallback ladder
     * server-side; HR is a single-variant match.
     *
     * Required: country ('cz'|'hr').
     * Optional: street, city, postal_code, number ("2186/1b") or
     * house_number/orientation_number/orientation_letter (parsed parts),
     * number_type ('house'|'registration', CZ-only).
     *
     * @param array<string, mixed> $payload
     * @param array<string> $include
     * @return array<string, mixed> { matches, fallback_step, ambiguous }
     */
    public static function lookup(array $payload, array $include = []): array
    {
        $path = 'v1/lookup';
        if ($include !== []) {
            $path .= '?' . http_build_query(['include' => implode(',', $include)]);
        }

        try {
            $response = self::postRequest(path: $path, data: $payload);
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var array<string, mixed> */
        return self::decodeOrThrow($response);
    }

    /**
     * Bulk version of lookup — items are processed independently.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string> $include
     * @return array<string, mixed> { results: [...] }
     */
    public static function lookupBatch(array $items, array $include = []): array
    {
        $path = 'v1/lookup/batch';
        if ($include !== []) {
            $path .= '?' . http_build_query(['include' => implode(',', $include)]);
        }

        try {
            $response = self::postRequest(
                path: $path,
                data: ['items' => $items],
                timeout: 60,
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var array<string, mixed> */
        return self::decodeOrThrow($response);
    }

    /**
     * Reverse geocoding — nearest addresses to a WGS84 coordinate.
     *
     * @param array<string> $include
     * @return list<array<string, mixed>> Sorted by ascending distance_m.
     */
    public static function reverse(
        string $country,
        float $lat,
        float $lon,
        float $radiusM = 500.0,
        int $limit = 10,
        array $include = [],
    ): array {
        $query = [
            'country' => $country,
            'lat' => $lat,
            'lon' => $lon,
            'radius_m' => $radiusM,
            'limit' => $limit,
        ];
        if ($include !== []) {
            $query['include'] = implode(',', $include);
        }

        try {
            $response = self::getRequest(path: 'v1/reverse', query: $query);
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var list<array<string, mixed>> */
        return self::decodeOrThrow($response);
    }

    /**
     * Fuzzy autocomplete on the formatted-address label. Tolerates typos,
     * partial words, and out-of-order tokens. Each match carries a `score`
     * field in [0, 1] for client-side thresholding.
     *
     * @param array<string> $include
     * @return list<array<string, mixed>>
     */
    public static function search(
        string $country,
        string $q,
        int $limit = 10,
        array $include = [],
    ): array {
        $query = ['country' => $country, 'q' => $q, 'limit' => $limit];
        if ($include !== []) {
            $query['include'] = implode(',', $include);
        }

        try {
            $response = self::getRequest(path: 'v1/search', query: $query);
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('Addresses API is unreachable: {0}', $e->getMessage()),
                previous: $e,
            );
        }

        /** @var list<array<string, mixed>> */
        return self::decodeOrThrow($response);
    }
}
