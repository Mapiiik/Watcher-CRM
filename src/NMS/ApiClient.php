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

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Closure;
use Throwable;

/**
 * API Client
 *
 * Nothing here throws. Watcher NMS is another application that may be down, misconfigured or
 * answering something unexpected, and none of that is a reason to lose the page - every caller
 * reads null as "Watcher NMS did not say", falls back to an empty list and tells the operator so.
 * What a failure must not do is pass for an answer, so it is written down and it is never kept.
 */
class ApiClient
{
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
     * What came of the asking during this request. {@see self::isAvailable()}
     */
    private static ?bool $answered = null;

    /**
     * Fetch access points method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API
     */
    public static function fetchAccessPoints(): ?CollectionInterface
    {
        $accessPoints = self::fetch('/api/access-points.json', 'accessPoints');

        return $accessPoints === null ? null : new Collection($accessPoints);
    }

    /**
     * Get access points method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API or from cache if valid
     */
    public static function getAccessPoints(): ?CollectionInterface
    {
        return self::remember(
            'access_points',
            function (): ?CollectionInterface {
                return self::fetchAccessPoints();
            },
        );
    }

    /**
     * Get access points list
     *
     * @return array<string>|null Return result from API or from cache if valid
     */
    public static function getAccessPointsList(bool $onlyActive = false): ?array
    {
        return self::remember(
            'access_points_list|' . ($onlyActive ? 'active' : 'all'),
            function () use ($onlyActive): ?array {
                $accessPoints = self::getAccessPoints();
                if ($accessPoints instanceof CollectionInterface) {
                    $list = $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL);

                    if ($onlyActive) {
                        return $list->match(['archived' => null])->combine('id', 'name')->toArray();
                    }

                    return $list->combine('id', 'name')->toArray();
                }

                return null;
            },
        );
    }

    /**
     * Fetch access point method
     *
     * @param string $id Access Point id.
     * @return \ArrayObject<string, mixed>|null Return result from API
     */
    public static function fetchAccessPoint(string $id): ?ArrayObject
    {
        $accessPoint = self::fetch('/api/access-points/' . $id . '.json', 'accessPoint');

        return $accessPoint === null ? null : new ArrayObject($accessPoint, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get access point method
     *
     * @param string $id Access Point id.
     * @return \ArrayObject<string, mixed>|null Return result from API or from cache if valid
     */
    public static function getAccessPoint(string $id): ?ArrayObject
    {
        return self::remember(
            'access_point_' . $id,
            function () use ($id): ?ArrayObject {
                return self::fetchAccessPoint($id);
            },
        );
    }

    /**
     * Fetch IP address ranges method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API
     */
    public static function fetchIpAddressRanges(): ?CollectionInterface
    {
        $ipAddressRanges = self::fetch('/api/ip-address-ranges.json', 'ipAddressRanges');

        return $ipAddressRanges === null ? null : new Collection($ipAddressRanges);
    }

    /**
     * Get IP address ranges method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API or from cache if valid
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getIpAddressRanges(): ?CollectionInterface
    {
        return self::remember(
            'ip_address_ranges',
            function (): ?CollectionInterface {
                return self::fetchIpAddressRanges();
            },
        );
    }

    /**
     * Fetch IP address range method
     *
     * @param string $id IP address range id.
     * @return \ArrayObject<string, mixed>|null Return result from API
     */
    public static function fetchIpAddressRange(string $id): ?ArrayObject
    {
        $ipAddressRange = self::fetch('/api/ip-address-ranges/' . $id . '.json', 'ipAddressRange');

        return $ipAddressRange === null ? null : new ArrayObject($ipAddressRange, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get IP address range method
     *
     * @param string $id IP address range id.
     * @return \ArrayObject<string, mixed>|null Return result from API or from cache if valid
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getIpAddressRange(string $id): ?ArrayObject
    {
        return self::remember(
            'ip_address_range_' . $id,
            function () use ($id): ?ArrayObject {
                return self::fetchIpAddressRange($id);
            },
        );
    }

    /**
     * Search IP address ranges method
     *
     * @param array<string> $search IP address ranges condidions.
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API
     */
    public static function searchIpAddressRanges(array $search): ?CollectionInterface
    {
        $ipAddressRanges = self::fetch('/api/ip-address-ranges/search.json', 'ipAddressRanges', $search);

        return $ipAddressRanges === null ? null : new Collection($ipAddressRanges);
    }

    /**
     * Get IP address ranges for IP method
     *
     * @param string $ipAddress IP address.
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API or from cache if valid
     */
    public static function getIpAddressRangesForIp(string $ipAddress): ?CollectionInterface
    {
        return self::remember(
            'ip_address_ranges_for_ip_' . strtr($ipAddress, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ipAddress): ?CollectionInterface {
                return self::searchIpAddressRanges(['ip_address' => $ipAddress]);
            },
        );
    }

    /**
     * Search RouterOS Devices method
     *
     * @param array<string> $search IP address ranges condidions.
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API
     */
    public static function searchRouterosDevices(array $search): ?CollectionInterface
    {
        $routerosDevices = self::fetch('/api/routeros-devices/search.json', 'routerosDevices', $search);

        return $routerosDevices === null ? null : new Collection($routerosDevices);
    }

    /**
     * Get RouterOS Device for IP method
     *
     * @param string $ipAddress IP address.
     * @return \Cake\Collection\CollectionInterface<mixed, mixed>|null Return result from API or from cache if valid
     */
    public static function getRouterosDevicesForIp(string $ipAddress): ?CollectionInterface
    {
        return self::remember(
            'routeros_devices_for_ip_' . strtr($ipAddress, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ipAddress): ?CollectionInterface {
                return self::searchRouterosDevices(['some_ip_address' => $ipAddress]);
            },
        );
    }

    /**
     * How the asking has gone so far.
     *
     * Whoever draws the page has to tell a Watcher NMS that did not answer from one that answered
     * nothing - the first is worth saying out loud, the second is just an empty column. Both arrive
     * here as null, so what actually happened is remembered rather than asked about again: a page
     * showing a hundred rows would otherwise wait out the timeout a hundred times over to find out
     * what the first row already knew.
     *
     * Kept for one request, which is as long as one page is drawn.
     *
     * @return bool|null Null where nothing was asked, false where a question went unanswered.
     */
    public static function isAvailable(): ?bool
    {
        return self::$answered;
    }

    /**
     * Whether there is a Watcher NMS to ask at all.
     *
     * @return bool
     */
    private static function isConfigured(): bool
    {
        return (string)Configure::read('Nms.url') !== '' && (string)Configure::read('Nms.key') !== '';
    }

    /**
     * Reads one thing from Watcher NMS, and hands back nothing where it was not read.
     *
     * The key is the one Watcher NMS wraps its answer in. An answer without it is an answer to a
     * different question - an error page, a login form, a changed API - and is not read as data.
     *
     * @param string $path What to read.
     * @param string $key What Watcher NMS calls the answer.
     * @param array<string, mixed> $query Anything to ask for beyond the path.
     * @return array<mixed>|null
     */
    private static function fetch(string $path, string $key, array $query = []): ?array
    {
        // Not being configured is a state, not a failure - an installation without a Watcher NMS says
        // so by leaving the address empty, and saying it again in the log every few minutes
        // helps nobody.
        if (!self::isConfigured()) {
            return null;
        }

        try {
            $client = Client::createFromUrl((string)Configure::read('Nms.url'));
            $client->setConfig('timeout', self::TIMEOUT);

            $response = $client->get($path, ['api_key' => Configure::read('Nms.key')] + $query);
        } catch (Throwable $e) {
            // The path and never the query: the API key is asked for as a query parameter, and a
            // log is read by more people than a configuration file is.
            Log::error(sprintf('Watcher NMS could not be asked about %s: %s', $path, $e->getMessage()));
            self::$answered = false;

            return null;
        }

        if (!$response->isOk()) {
            Log::error(sprintf('Watcher NMS answered %d asking about %s.', $response->getStatusCode(), $path));
            self::$answered = false;

            return null;
        }

        $body = $response->getJson();

        if (!is_array($body) || !isset($body[$key]) || !is_array($body[$key])) {
            Log::warning(sprintf('Watcher NMS answered %s without `%s` in it.', $path, $key));
            self::$answered = false;

            return null;
        }

        // One answer does not undo another that never came: a page is only as whole as its
        // emptiest column.
        self::$answered ??= true;

        return $body[$key];
    }

    /**
     * What is kept, or what Watcher NMS says now.
     *
     * {@see \Cake\Cache\Cache::remember()} in every respect but one: an answer that was not given
     * is not written down. Keeping it would hand the next caller a failure dressed as an answer,
     * and Watcher NMS coming back up would not be noticed until it ran out.
     *
     * @template T
     * @param string $key Where the answer is kept.
     * @param \Closure(): T $fetch How to ask, when there is nothing kept.
     * @return T|null
     */
    private static function remember(string $key, Closure $fetch): mixed
    {
        $cached = Cache::read($key, self::CACHE_CONFIG);
        if ($cached !== null) {
            return $cached;
        }

        $answer = $fetch();
        if ($answer !== null) {
            Cache::write($key, $answer, self::CACHE_CONFIG);
        }

        return $answer;
    }
}
