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
}
