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
namespace App;

use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Client;
use Cake\ORM\Entity;

/**
 * API Client
 */
class ApiClient
{
    /**
     * Fetch access points method
     *
     * @return \Cake\Collection\CollectionInterface|null Return result from API
     */
    public static function fetchAccessPoints(): ?CollectionInterface
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/access-points.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ]);

            $json = $response->getJson();
            if (isset($json['accessPoints'])) {
                $collection = new Collection($json['accessPoints']);

                return $collection;
            }
        }

        return null;
    }

    /**
     * Get access points method
     *
     * @return \Cake\Collection\CollectionInterface|null Return result from API or from cache if valid
     */
    public static function getAccessPoints(): ?CollectionInterface
    {
        return Cache::remember(
            'access_points',
            function () {
                return ApiClient::fetchAccessPoints();
            },
            'api_client'
        );
    }

    /**
     * Fetch access point method
     *
     * @param string $id Access Point id.
     * @return \Cake\ORM\Entity|null Return result from API
     */
    public static function fetchAccessPoint(string $id): ?Entity
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/access-points/' . $id . '.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ]);

            $json = $response->getJson();
            if (isset($json['accessPoint'])) {
                $entity = new Entity($json['accessPoint']);

                return $entity;
            }
        }

        return null;
    }

    /**
     * Get access point method
     *
     * @param string $id Access Point id.
     * @return \Cake\ORM\Entity|null Return result from API or from cache if valid
     */
    public static function getAccessPoint(string $id): ?Entity
    {
        return Cache::remember(
            'access_point_' . $id,
            function () use ($id) {
                return ApiClient::fetchAccessPoint($id);
            },
            'api_client'
        );
    }

    /**
     * Search access point method
     *
     * @param string[] $search access point condidions.
     * @return \Cake\Collection\CollectionInterface|null Return result from API
     */
    public static function searchAccessPoints(array $search): ?CollectionInterface
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/access-points/search.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ] + $search);

            $json = $response->getJson();
            if (isset($json['accessPoints'])) {
                $collection = new Collection($json['accessPoints']);

                return $collection;
            }
        }

        return null;
    }

    /**
     * Get access points for IP method
     *
     * @param string $ip IP address.
     * @return \Cake\Collection\CollectionInterface|null Return result from API or from cache if valid
     */
    public static function getAccessPointsForIp(string $ip): ?CollectionInterface
    {
        return Cache::remember(
            'access_points_for_ip_' . strtr($ip, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ip) {
                return ApiClient::searchAccessPoints(['ip_address' => $ip]);
            },
            'api_client'
        );
    }

    /**
     * Fetch IP address ranges method
     *
     * @return \Cake\Collection\CollectionInterface|null Return result from API
     */
    public static function fetchIpAddressRanges(): ?CollectionInterface
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/ip-address-ranges.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ]);

            $json = $response->getJson();
            if (isset($json['ipAddressRanges'])) {
                $collection = new Collection($json['ipAddressRanges']);

                return $collection;
            }
        }

        return null;
    }

    /**
     * Get IP address ranges method
     *
     * @return \Cake\Collection\CollectionInterface|null Return result from API or from cache if valid
     */
    public static function getIpAddressRanges(): ?CollectionInterface
    {
        return Cache::remember(
            'ip_address_ranges',
            function () {
                return ApiClient::fetchIpAddressRanges();
            },
            'api_client'
        );
    }

    /**
     * Fetch IP address range method
     *
     * @param string $id IP address range id.
     * @return \Cake\ORM\Entity|null Return result from API
     */
    public static function fetchIpAddressRange(string $id): ?Entity
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/ip-address-ranges/' . $id . '.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ]);

            $json = $response->getJson();
            if (isset($json['ipAddressRange'])) {
                $entity = new Entity($json['ipAddressRange']);

                return $entity;
            }
        }

        return null;
    }

    /**
     * Get IP address range method
     *
     * @param string $id IP address range id.
     * @return \Cake\ORM\Entity|null Return result from API or from cache if valid
     */
    public static function getIpAddressRange(string $id): ?Entity
    {
        return Cache::remember(
            'ip_address_range_' . $id,
            function () use ($id) {
                return ApiClient::fetchIpAddressRange($id);
            },
            'api_client'
        );
    }

    /**
     * Search IP address ranges method
     *
     * @param string[] $search IP address ranges condidions.
     * @return \Cake\Collection\CollectionInterface|null Return result from API
     */
    public static function searchIpAddressRanges(array $search): ?CollectionInterface
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/ip-address-ranges/search.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ] + $search);

            $json = $response->getJson();
            if (isset($json['ipAddressRanges'])) {
                $collection = new Collection($json['ipAddressRanges']);

                return $collection;
            }
        }

        return null;
    }

    /**
     * Get IP address ranges for IP method
     *
     * @param string $ip IP address.
     * @return \Cake\Collection\CollectionInterface|null Return result from API or from cache if valid
     */
    public static function getIpAddressRangesForIp(string $ip): ?CollectionInterface
    {
        return Cache::remember(
            'ip_address_ranges_for_ip_' . strtr($ip, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ip) {
                return ApiClient::searchIpAddressRanges(['ip_address' => $ip]);
            },
            'api_client'
        );
    }

    /**
     * Search RouterOS Devices method
     *
     * @param string[] $search IP address ranges condidions.
     * @return \Cake\Collection\CollectionInterface|null Return result from API
     */
    public static function searchRouterosDevices(array $search): ?CollectionInterface
    {
        if (env('WATCHER_NMS_URL') && env('WATCHER_NMS_KEY')) {
            $http = Client::createFromUrl(env('WATCHER_NMS_URL'));
            $response = $http->get('/api/routeros-devices/search.json', [
                'api_key' => env('WATCHER_NMS_KEY'),
            ] + $search);

            $json = $response->getJson();
            if (isset($json['routerosDevices'])) {
                $collection = new Collection($json['routerosDevices']);

                return $collection;
            }
        }

        return null;
    }

    /**
     * Get RouterOS Device for IP method
     *
     * @param string $ip IP address.
     * @return \Cake\Collection\CollectionInterface|null Return result from API or from cache if valid
     */
    public static function getRouterosDevicesForIp(string $ip): ?CollectionInterface
    {
        return Cache::remember(
            'routeros_devices_for_ip_' . strtr($ip, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ip) {
                return ApiClient::searchRouterosDevices(['ip_address' => $ip]);
            },
            'api_client'
        );
    }
}
