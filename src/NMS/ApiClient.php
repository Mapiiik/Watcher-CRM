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

/**
 * API Client
 */
class ApiClient
{
    /**
     * Fetch access points method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API
     */
    public static function fetchAccessPoints(): ?CollectionInterface
    {
        if (Configure::read('Nms.url') && Configure::read('Nms.key')) {
            $http = Client::createFromUrl((string)Configure::read('Nms.url'));
            $response = $http->get('/api/access-points.json', [
                'api_key' => Configure::read('Nms.key'),
            ]);

            $json = $response->getJson();
            if (isset($json['accessPoints'])) {
                return new Collection($json['accessPoints']);
            }
        }

        return null;
    }

    /**
     * Get access points method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API or from cache if valid
     */
    public static function getAccessPoints(): ?CollectionInterface
    {
        return Cache::remember(
            'access_points',
            function (): ?CollectionInterface {
                return self::fetchAccessPoints();
            },
            'api_client',
        );
    }

    /**
     * Get access points list
     *
     * @return array<string>|null Return result from API or from cache if valid
     */
    public static function getAccessPointsList(bool $onlyActive = false): ?array
    {
        return Cache::remember(
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
            'api_client',
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
        if (Configure::read('Nms.url') && Configure::read('Nms.key')) {
            $http = Client::createFromUrl((string)Configure::read('Nms.url'));
            $response = $http->get('/api/access-points/' . $id . '.json', [
                'api_key' => Configure::read('Nms.key'),
            ]);

            $json = $response->getJson();
            if (isset($json['accessPoint'])) {
                return new ArrayObject($json['accessPoint'], ArrayObject::ARRAY_AS_PROPS);
            }
        }

        return null;
    }

    /**
     * Get access point method
     *
     * @param string $id Access Point id.
     * @return \ArrayObject<string, mixed>|null Return result from API or from cache if valid
     */
    public static function getAccessPoint(string $id): ?ArrayObject
    {
        return Cache::remember(
            'access_point_' . $id,
            function () use ($id): ?ArrayObject {
                return self::fetchAccessPoint($id);
            },
            'api_client',
        );
    }

    /**
     * Fetch IP address ranges method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API
     */
    public static function fetchIpAddressRanges(): ?CollectionInterface
    {
        if (Configure::read('Nms.url') && Configure::read('Nms.key')) {
            $http = Client::createFromUrl((string)Configure::read('Nms.url'));
            $response = $http->get('/api/ip-address-ranges.json', [
                'api_key' => Configure::read('Nms.key'),
            ]);

            $json = $response->getJson();
            if (isset($json['ipAddressRanges'])) {
                return new Collection($json['ipAddressRanges']);
            }
        }

        return null;
    }

    /**
     * Get IP address ranges method
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API or from cache if valid
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function getIpAddressRanges(): ?CollectionInterface
    {
        return Cache::remember(
            'ip_address_ranges',
            function (): ?CollectionInterface {
                return self::fetchIpAddressRanges();
            },
            'api_client',
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
        if (Configure::read('Nms.url') && Configure::read('Nms.key')) {
            $http = Client::createFromUrl((string)Configure::read('Nms.url'));
            $response = $http->get('/api/ip-address-ranges/' . $id . '.json', [
                'api_key' => Configure::read('Nms.key'),
            ]);

            $json = $response->getJson();
            if (isset($json['ipAddressRange'])) {
                return new ArrayObject($json['ipAddressRange'], ArrayObject::ARRAY_AS_PROPS);
            }
        }

        return null;
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
        return Cache::remember(
            'ip_address_range_' . $id,
            function () use ($id): ?ArrayObject {
                return self::fetchIpAddressRange($id);
            },
            'api_client',
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
        if (Configure::read('Nms.url') && Configure::read('Nms.key')) {
            $http = Client::createFromUrl((string)Configure::read('Nms.url'));
            $response = $http->get('/api/ip-address-ranges/search.json', [
                'api_key' => Configure::read('Nms.key'),
            ] + $search);

            $json = $response->getJson();
            if (isset($json['ipAddressRanges'])) {
                return new Collection($json['ipAddressRanges']);
            }
        }

        return null;
    }

    /**
     * Get IP address ranges for IP method
     *
     * @param string $ipAddress IP address.
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null Return result from API or from cache if valid
     */
    public static function getIpAddressRangesForIp(string $ipAddress): ?CollectionInterface
    {
        return Cache::remember(
            'ip_address_ranges_for_ip_' . strtr($ipAddress, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ipAddress): ?CollectionInterface {
                return self::searchIpAddressRanges(['ip_address' => $ipAddress]);
            },
            'api_client',
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
        if (Configure::read('Nms.url') && Configure::read('Nms.key')) {
            $http = Client::createFromUrl((string)Configure::read('Nms.url'));
            $response = $http->get('/api/routeros-devices/search.json', [
                'api_key' => Configure::read('Nms.key'),
            ] + $search);

            $json = $response->getJson();
            if (isset($json['routerosDevices'])) {
                return new Collection($json['routerosDevices']);
            }
        }

        return null;
    }

    /**
     * Get RouterOS Device for IP method
     *
     * @param string $ipAddress IP address.
     * @return \Cake\Collection\CollectionInterface<mixed, mixed>|null Return result from API or from cache if valid
     */
    public static function getRouterosDevicesForIp(string $ipAddress): ?CollectionInterface
    {
        return Cache::remember(
            'routeros_devices_for_ip_' . strtr($ipAddress, ['.' => '-', ':' => '-', '/' => '-mask-']),
            function () use ($ipAddress): ?CollectionInterface {
                return self::searchRouterosDevices(['some_ip_address' => $ipAddress]);
            },
            'api_client',
        );
    }
}
