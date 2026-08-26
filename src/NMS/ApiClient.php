<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\NMS;

use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;
use App\NMS\Dto\AccessPoint;
use App\NMS\Provider\NmsPayloadNormalizer;
use Cake\Cache\Cache;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Client;
use Throwable;

/**
 * API Client
 *
 * Nothing here throws. Watcher NMS is another application that may be down, misconfigured or
 * answering something unexpected, and none of that is a reason to lose the page - so every reading
 * comes back as an {@see \App\Http\Answer}, and the caller says what a failure is worth. A page
 * draws itself without the answer and remarks on it; a command asks for `orFail()` and stops.
 *
 * What a failure must never do is pass for an answer, so it is written down and it is never kept.
 */
class ApiClient
{
    use WritesDownFailuresTrait;

    /**
     * Where answers are kept.
     */
    private const CACHE_CONFIG = 'api_client';

    /**
     * How long to wait for Watcher NMS, in seconds.
     *
     * A service of our own on the same network answers in milliseconds. The default of thirty
     * seconds is not a wait, it is how long a page hangs when Watcher NMS is unreachable - and a
     * list asks about every row it shows.
     */
    private const TIMEOUT = 10;

    /**
     * What this service is called in the log.
     */
    private const SERVICE = 'Watcher NMS';

    /**
     * The access points Watcher NMS keeps.
     *
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\AccessPoint>>
     */
    public static function getAccessPoints(): Answer
    {
        return self::read('access_points', '/api/access-points.json', 'accessPoints')
            ->map(NmsPayloadNormalizer::accessPoints(...));
    }

    /**
     * The one access point a record names, or null where Watcher NMS keeps no such point.
     *
     * Picked out of every point it keeps rather than asked after by itself. A listing shows many
     * rows and that reading is fetched once for all of them, where a point apiece would be a
     * request apiece - and the same reading already stands behind the lists a form picks from.
     *
     * An answer that came to nothing is passed on as it is: not knowing whether the point is there
     * is not the same as knowing it is not, and only the caller can say what that is worth.
     *
     * @param string $id The number the point is kept under.
     * @return \App\Http\Answer<\App\NMS\Dto\AccessPoint|null>
     */
    public static function getAccessPoint(string $id): Answer
    {
        return self::getAccessPoints()->map(
            fn(CollectionInterface $accessPoints): ?AccessPoint => $accessPoints
                ->filter(fn(AccessPoint $accessPoint): bool => $accessPoint->id === $id)
                ->first(),
        );
    }

    /**
     * The access points as a list to pick from, by number and name.
     *
     * Kept of its own beside the points themselves: a listing asks for it once a row, and sorting
     * a few hundred masts that many times over is work nobody sees.
     *
     * @param bool $onlyActive Whether to leave out the points that have been put out of use.
     * @return \App\Http\Answer<array<string, string>>
     */
    public static function getAccessPointsList(bool $onlyActive = false): Answer
    {
        $key = 'access_points_list|' . ($onlyActive ? 'active' : 'all');

        if (!self::configured()) {
            return Answer::notAsked();
        }

        $cached = Cache::read($key, self::CACHE_CONFIG);
        if ($cached !== null) {
            return Answer::of($cached);
        }

        /** @var \App\Http\Answer<array<string, string>> $answer */
        $answer = self::getAccessPoints()->map(
            fn(CollectionInterface $accessPoints): array => $accessPoints
                ->filter(fn(AccessPoint $accessPoint): bool => !$onlyActive || !$accessPoint->isArchived())
                ->sortBy(fn(AccessPoint $accessPoint): string => (string)$accessPoint->name, SORT_ASC, SORT_NATURAL)
                ->combine(
                    fn(AccessPoint $accessPoint): string => $accessPoint->id,
                    fn(AccessPoint $accessPoint): string => (string)$accessPoint->name,
                )
                ->toArray(),
        );

        if ($answer->ok()) {
            Cache::write($key, $answer->data, self::CACHE_CONFIG);
        }

        return $answer;
    }

    /**
     * The ranges of addresses Watcher NMS keeps.
     *
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getIpAddressRanges(): Answer
    {
        return self::read('ip_address_ranges', '/api/ip-address-ranges.json', 'ipAddressRanges')
            ->map(NmsPayloadNormalizer::ipAddressRanges(...));
    }

    /**
     * One range of addresses.
     *
     * @param string $id The number Watcher NMS keeps the range under.
     * @return \App\Http\Answer<\App\NMS\Dto\IpAddressRange|null>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getIpAddressRange(string $id): Answer
    {
        return self::read(
            'ip_address_range_' . $id,
            '/api/ip-address-ranges/' . $id . '.json',
            'ipAddressRange',
        )->map(NmsPayloadNormalizer::ipAddressRange(...));
    }

    /**
     * The ranges matching what is asked about them.
     *
     * Not kept: the conditions differ with every form that asks, and a form wants what is there
     * now rather than what was there a few minutes ago.
     *
     * @param array<string, mixed> $search What to match the ranges on.
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>>
     */
    public static function searchIpAddressRanges(array $search): Answer
    {
        return self::ask('/api/ip-address-ranges/search.json', 'ipAddressRanges', $search)
            ->map(NmsPayloadNormalizer::ipAddressRanges(...));
    }

    /**
     * The ranges an address falls in.
     *
     * @param string $ipAddress The address or network to look for.
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>>
     */
    public static function getIpAddressRangesForIp(string $ipAddress): Answer
    {
        return self::read(
            'ip_address_ranges_for_ip_' . self::keyFor($ipAddress),
            '/api/ip-address-ranges/search.json',
            'ipAddressRanges',
            ['ip_address' => $ipAddress],
        )->map(NmsPayloadNormalizer::ipAddressRanges(...));
    }

    /**
     * The devices matching what is asked about them.
     *
     * @param array<string, mixed> $search What to match the devices on.
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function searchRouterosDevices(array $search): Answer
    {
        return self::ask('/api/routeros-devices/search.json', 'routerosDevices', $search)
            ->map(NmsPayloadNormalizer::routerosDevices(...));
    }

    /**
     * The devices answering at an address.
     *
     * @param string $ipAddress The address to look for.
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>>
     */
    public static function getRouterosDevicesForIp(string $ipAddress): Answer
    {
        return self::read(
            'routeros_devices_for_ip_' . self::keyFor($ipAddress),
            '/api/routeros-devices/search.json',
            'routerosDevices',
            ['some_ip_address' => $ipAddress],
        )->map(NmsPayloadNormalizer::routerosDevices(...));
    }

    /**
     * Whether there is a Watcher NMS to ask at all.
     *
     * @return bool
     */
    private static function configured(): bool
    {
        return (string)Configure::read('Nms.url') !== '' && (string)Configure::read('Nms.key') !== '';
    }

    /**
     * What is kept, or what Watcher NMS says now.
     *
     * What goes into the cache is the answer as it arrived, never the things read out of it: a
     * deploy that changes one of those classes would otherwise be met with whatever the cache
     * still holds. An answer that was not given is not written down at all - keeping it would hand
     * the next caller a failure dressed as an answer, and Watcher NMS coming back up would not be
     * noticed until it ran out.
     *
     * @param string $key Where the answer is kept.
     * @param string $path What to read.
     * @param string $answerKey What Watcher NMS calls the answer.
     * @param array<string, mixed> $query Anything to ask for beyond the path.
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    private static function read(string $key, string $path, string $answerKey, array $query = []): Answer
    {
        // Asked before the cache rather than after it. A reading kept from an address that has
        // since been taken out of the configuration is a reading of a system this installation no
        // longer has, and handing it over says the place is known to be there, or known not to be,
        // where the truth is that there is nobody left to ask.
        if (!self::configured()) {
            return Answer::notAsked();
        }

        $cached = Cache::read($key, self::CACHE_CONFIG);
        if ($cached !== null) {
            return Answer::of($cached);
        }

        $answer = self::ask($path, $answerKey, $query);

        if ($answer->ok()) {
            Cache::write($key, $answer->data, self::CACHE_CONFIG);
        }

        return $answer;
    }

    /**
     * Reads one thing from Watcher NMS.
     *
     * The key is the one Watcher NMS wraps its answer in. An answer without it is an answer to a
     * different question - an error page, a login form, a changed API - and is not read as data.
     *
     * @param string $path What to read.
     * @param string $answerKey What Watcher NMS calls the answer.
     * @param array<string, mixed> $query Anything to ask for beyond the path.
     * @return \App\Http\Answer<array<mixed>>
     */
    private static function ask(string $path, string $answerKey, array $query = []): Answer
    {
        // Not being configured is a state, not a failure - an installation without a Watcher NMS
        // says so by leaving the address empty, and saying it again in the log every few minutes
        // helps nobody. Asked here as well as above the cache, so that no way in reaches the wire
        // without passing it.
        if (!self::configured()) {
            return Answer::notAsked();
        }

        // The address it was asked at and never the question: the key is asked for as a query
        // parameter, and a log is read by more people than a configuration file is.
        $where = rtrim((string)Configure::read('Nms.url'), '/') . $path;

        try {
            $client = Client::createFromUrl((string)Configure::read('Nms.url'));
            $client->setConfig('timeout', self::TIMEOUT);

            $response = $client->get($path, ['api_key' => Configure::read('Nms.key')] + $query);
        } catch (Throwable $e) {
            return self::unreachable(self::SERVICE, $where, $e->getMessage());
        }

        if (!$response->isOk()) {
            return self::refused(self::SERVICE, $where, $response->getStatusCode());
        }

        $body = $response->getJson();

        if (!is_array($body) || !isset($body[$answerKey]) || !is_array($body[$answerKey])) {
            // Not an outage but a misunderstanding: something answered, it just was not this.
            return self::unexpected(
                self::SERVICE,
                $where,
                sprintf('no `%s` in it', $answerKey),
                'warning',
            );
        }

        return Answer::of($body[$answerKey]);
    }

    /**
     * An address as a cache key may spell it.
     *
     * @param string $ipAddress The address or network.
     * @return string
     */
    private static function keyFor(string $ipAddress): string
    {
        return strtr($ipAddress, ['.' => '-', ':' => '-', '/' => '-mask-']);
    }
}
