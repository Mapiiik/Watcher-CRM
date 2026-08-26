<?php
declare(strict_types=1);

namespace App\Addresses;

use App\Addresses\Dto\Address;
use App\Addresses\Dto\Batch;
use App\Addresses\Provider\AddressPayloadNormalizer;
use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Closure;
use Throwable;

/**
 * Talking to the address registries.
 *
 * Every reading comes back as an {@see \App\Http\Answer}, so the caller says what a failure is
 * worth rather than the client deciding for it - a form asks for `orFail()` and shows what went
 * wrong, a bulk run passes over the chunk and keeps the addresses it already had.
 */
class ApiClient
{
    use WritesDownFailuresTrait;

    /**
     * What this service is called in the log.
     */
    private const SERVICE = 'The Addresses API';

    /**
     * Build the configured Cake HTTP client.
     */
    private static function http(int $timeout = 30): Client
    {
        $headers = ['Accept' => 'application/json'];

        $apiKey = (string)Configure::read('Addresses.key');
        if ($apiKey !== '') {
            $headers['X-API-Key'] = $apiKey;
        }

        return new Client([
            'headers' => $headers,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Resolve a relative API path against the configured address of the API.
     */
    private static function url(string $path): string
    {
        return (string)Configure::read('Addresses.url') . '/' . ltrim($path, '/');
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
     * Read one thing from the registry.
     *
     * Not being configured is a state, not a failure - an installation without an address registry
     * says so by leaving the address empty, and nobody asked.
     *
     * @param \Closure(): \Cake\Http\Client\Response $ask How to ask.
     * @param string $path What is being read, for the message.
     * @param bool $missingIsAnAnswer Whether a 404 means the registry knows of no such thing.
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    private static function ask(Closure $ask, string $path, bool $missingIsAnAnswer = false): Answer
    {
        if ((string)Configure::read('Addresses.url') === '') {
            return Answer::notAsked();
        }

        $where = self::url($path);

        try {
            $response = $ask();
        } catch (Throwable $e) {
            return self::unreachable(self::SERVICE, $where, $e->getMessage());
        }

        if ($missingIsAnAnswer && $response->getStatusCode() === 404) {
            return Answer::of(null);
        }

        $data = $response->getJson();

        if (!$response->isOk()) {
            return self::refused(self::SERVICE, $where, $response->getStatusCode(), self::extractError($data));
        }

        if (!is_array($data)) {
            return self::unexpected(self::SERVICE, $where, 'not an object');
        }

        return Answer::of($data);
    }

    /**
     * Read one thing that the other side either holds or does not.
     *
     * A 404 is then an answer rather than a failure: it says there is no such thing, which is what
     * the caller asked to find out.
     *
     * @param \Closure(): \Cake\Http\Client\Response $ask How to ask.
     * @param string $path What is being read, for the message.
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    private static function readOrMissing(Closure $ask, string $path): Answer
    {
        return self::ask($ask, $path, missingIsAnAnswer: true);
    }

    /**
     * Read one thing the other side is expected to hold.
     *
     * @param \Closure(): \Cake\Http\Client\Response $ask How to ask.
     * @param string $path What is being read, for the message.
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    private static function read(Closure $ask, string $path): Answer
    {
        /** @var \App\Http\Answer<array<int|string, mixed>> $answer */
        $answer = self::ask($ask, $path, missingIsAnAnswer: false);

        return $answer;
    }

    /**
     * What is kept, or what the registry says now.
     *
     * The body as it arrived is what goes into the cache, never the addresses read out of it, and
     * an answer that never came is not kept at all.
     *
     * @template TKept
     * @param string $key Where the answer is kept.
     * @param \Closure(): \App\Http\Answer<TKept> $ask How to ask, when there is nothing kept.
     * @return \App\Http\Answer<TKept>
     */
    private static function remember(string $key, Closure $ask): Answer
    {
        // Asked before the cache rather than after it: a reading kept from an address that has
        // since been taken out of the configuration is a reading of a registry this installation
        // no longer has.
        if ((string)Configure::read('Addresses.url') === '') {
            return Answer::notAsked();
        }

        $cached = Cache::read($key, 'addresses_api');
        if ($cached !== null) {
            return Answer::of($cached);
        }

        $answer = $ask();

        if ($answer->ok() && $answer->data !== null) {
            Cache::write($key, $answer->data, 'addresses_api');
        }

        return $answer;
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
     * @return \App\Http\Answer<array<int|string, mixed>>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function health(): Answer
    {
        return self::read(fn(): Response => self::getRequest(path: 'v1/health', timeout: 5), 'v1/health');
    }

    /**
     * Dataset metadata — row counts and last-refresh timestamps per table.
     *
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    public static function meta(): Answer
    {
        return self::read(fn(): Response => self::getRequest(path: 'v1/meta', timeout: 5), 'v1/meta');
    }

    /**
     * Cached variant of meta(). TTL is governed by the `addresses_api` cache config.
     *
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    public static function metaFromCache(): Answer
    {
        return self::remember('addresses_meta', self::meta(...));
    }

    /**
     * Look up a single address by its registry id (kod_adm for CZ,
     * ogc_fid for HR). Returns null if the id is not present.
     *
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return \App\Http\Answer<\App\Addresses\Dto\Address|null>
     */
    public static function byId(string $source, string $registryId, array $include = []): Answer
    {
        return self::rawById($source, $registryId, $include)
            ->map(fn(?array $body): ?Address => $body === null ? null : AddressPayloadNormalizer::address($body));
    }

    /**
     * Cached variant of byId(). TTL is governed by the `addresses_api` cache config.
     *
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return \App\Http\Answer<\App\Addresses\Dto\Address|null>
     */
    public static function byIdFromCache(string $source, string $registryId, array $include = []): Answer
    {
        // The key includes an `include` so that `?include=raw` does not share the cache with the default call.
        $key = sprintf(
            'address_%s_%s_%s',
            $source,
            $registryId,
            $include === [] ? 'normalized' : md5(implode(',', $include)),
        );

        return self::remember($key, fn(): Answer => self::rawById($source, $registryId, $include))
            ->map(fn(?array $body): ?Address => $body === null ? null : AddressPayloadNormalizer::address($body));
    }

    /**
     * The same reading as {@see self::byId()}, stopping at the body so that what is kept is the
     * answer as it arrived rather than the address read out of it.
     *
     * @param string $source Which registry to ask.
     * @param string $registryId The number that registry keeps the address under.
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    private static function rawById(string $source, string $registryId, array $include): Answer
    {
        $query = $include !== [] ? ['include' => implode(',', $include)] : [];

        $path = sprintf('v1/addresses/%s/%s', $source, $registryId);

        return self::readOrMissing(
            fn(): Response => self::getRequest(path: $path, query: $query),
            $path,
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
     * @return \App\Http\Answer<\App\Addresses\Dto\Batch>
     */
    public static function byIdBatch(array $items, array $include = []): Answer
    {
        return self::rawByIdBatch($items, $include)
            ->map(fn(?array $body): Batch => AddressPayloadNormalizer::batch($body ?? []));
    }

    /**
     * The same reading as {@see self::byIdBatch()}, stopping at the body.
     *
     * @param array<int, array{source: string, registry_id: string}> $items What to ask about.
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    private static function rawByIdBatch(array $items, array $include): Answer
    {
        return self::read(fn(): Response => self::postRequest(
            path: self::withInclude('v1/addresses/batch', $include),
            data: ['items' => $items],
            timeout: 60,
        ), 'v1/addresses/batch');
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
     * @param array<int, array<string, string>> $items What to ask about, as source and id.
     * @param array<string> $include Optional ?include= values, e.g. ['raw']
     * @return \App\Http\Answer<\App\Addresses\Dto\Batch>
     * @phpstan-param array<int, array{source: string, registry_id: string}> $items
     */
    public static function byIdBatchFromCache(array $items, array $include = []): Answer
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

        return self::remember($key, fn(): Answer => self::rawByIdBatch($items, $include))
            ->map(fn(?array $body): Batch => AddressPayloadNormalizer::batch($body ?? []));
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
     * @return \App\Http\Answer<\App\Addresses\Dto\Lookup>
     */
    public static function lookup(array $payload, array $include = []): Answer
    {
        return self::read(fn(): Response => self::postRequest(
            path: self::withInclude('v1/lookup', $include),
            data: $payload,
        ), 'v1/lookup')->map(AddressPayloadNormalizer::lookup(...));
    }

    /**
     * Bulk version of lookup — items are processed independently.
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<string> $include
     * @return \App\Http\Answer<list<\App\Addresses\Dto\Lookup>>
     */
    public static function lookupBatch(array $items, array $include = []): Answer
    {
        return self::read(fn(): Response => self::postRequest(
            path: self::withInclude('v1/lookup/batch', $include),
            data: ['items' => $items],
            timeout: 60,
        ), 'v1/lookup/batch')->map(AddressPayloadNormalizer::lookups(...));
    }

    /**
     * Reverse geocoding — nearest addresses to a WGS84 coordinate.
     *
     * @param array<string> $include
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\Addresses\Dto\Address>>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function reverse(
        string $country,
        float $lat,
        float $lon,
        float $radiusM = 500.0,
        int $limit = 10,
        array $include = [],
    ): Answer {
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

        return self::read(fn(): Response => self::getRequest(path: 'v1/reverse', query: $query), 'v1/reverse')
            ->map(AddressPayloadNormalizer::addresses(...));
    }

    /**
     * Fuzzy autocomplete on the formatted-address label. Tolerates typos,
     * partial words, and out-of-order tokens. Each match carries a `score`
     * field in [0, 1] for client-side thresholding.
     *
     * @param array<string> $include
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\Addresses\Dto\Address>>
     */
    public static function search(
        string $country,
        string $q,
        int $limit = 10,
        array $include = [],
    ): Answer {
        $query = ['country' => $country, 'q' => $q, 'limit' => $limit];
        if ($include !== []) {
            $query['include'] = implode(',', $include);
        }

        return self::read(fn(): Response => self::getRequest(path: 'v1/search', query: $query), 'v1/search')
            ->map(AddressPayloadNormalizer::addresses(...));
    }

    /**
     * A path with the extras the caller asked the registry to carry.
     *
     * @param string $path The path itself.
     * @param array<string> $include What to ask the registry to add.
     * @return string
     */
    private static function withInclude(string $path, array $include): string
    {
        if ($include === []) {
            return $path;
        }

        return $path . '?' . http_build_query(['include' => implode(',', $include)]);
    }
}
