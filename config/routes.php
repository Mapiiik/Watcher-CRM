<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

/*
 * Redirect /legacy/ URLs to /admin/ with all parameters if not called from CLI
 */
/*
if (PHP_SAPI !== 'cli') {
    $url =
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    if (mb_strpos($url, '/legacy/') !== false) {
        header('Location: ' . mb_ereg_replace('/legacy/', '/', $url), true, 303);
        die;
    }
    if (mb_strpos($url, '/admin/') !== false) {
        header('Location: ' . mb_ereg_replace('/admin/', '/', $url), true, 303);
        die;
    }
}
*/

/*
 * This file is loaded in the context of the `Application` class.
  * So you can use  `$this` to reference the application class instance
  * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->setExtensions(['pdf', 'csv']);

        /*
        * Contracts - nested routes
        */
        $builder
            ->connect('/customers/{customer_id}/contracts/{contract_id}', [
                'controller' => 'Contracts',
                'action' => 'view',
            ])
            ->setPatterns([
                'customer_id' => RouteBuilder::UUID,
                'contract_id' => RouteBuilder::UUID,
            ])
            ->setPass(['contract_id']);

        $builder
            ->connect('/customers/{customer_id}/contracts/{contract_id}/{action}', [
                'controller' => 'Contracts',
            ])
            ->setPatterns([
                'action' => 'edit|delete|print|set-dates-for-related-borrowed-equipments|terminate-related-billings',
                'customer_id' => RouteBuilder::UUID,
                'contract_id' => RouteBuilder::UUID,
            ])
            ->setPass(['contract_id']);

        $builder
            ->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}', [
                'action' => 'index',
            ])
            ->setPatterns([
                'customer_id' => RouteBuilder::UUID,
                'contract_id' => RouteBuilder::UUID,
            ]);

        $builder
            ->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{action}/*', [])
            ->setPatterns([
                'customer_id' => RouteBuilder::UUID,
                'contract_id' => RouteBuilder::UUID,
            ]);

        /*
        * Customers - nested routes
        */
        $builder
            ->connect('/customers/{customer_id}', [
                'controller' => 'Customers',
                'action' => 'view',
            ])
            ->setPatterns([
                'customer_id' => RouteBuilder::UUID,
            ])
            ->setPass(['customer_id']);

        $builder
            ->connect('/customers/{customer_id}/{action}', [
                'controller' => 'Customers',
            ])
            ->setPatterns([
                'action' => 'edit|delete|print',
                'customer_id' => RouteBuilder::UUID,
            ])
            ->setPass(['customer_id']);

        $builder
            ->connect('/customers/{customer_id}/{controller}', [
                'action' => 'index',
            ])
            ->setPatterns([
                'customer_id' => RouteBuilder::UUID,
            ]);

        $builder
            ->connect('/customers/{customer_id}/{controller}/{action}/*', [])
            ->setPatterns([
                'customer_id' => RouteBuilder::UUID,
            ]);

        // Default redirect
        $builder->redirect('/', ['controller' => 'Customers', 'action' => 'index'], ['status' => 303]);

        /*
        * ...and connect the rest of 'Pages' controller's URLs.
        */
        $builder->connect('/pages/*', 'Pages::display');

        /*
        * Connect catchall routes for all controllers.
        *
        * The `fallbacks` method is a shortcut for
        *
        * ```
        * $builder->connect('/:controller', ['action' => 'index']);
        * $builder->connect('/:controller/:action/*', []);
        * ```
        *
        * You can remove these routes once you've connected the
        * routes you want in your application.
        */
        $builder->fallbacks();
    });

    /*
    * If you need a different set of middleware or none at all,
    * open new scope and define routes there.
    *
    * ```
    * $routes->scope('/api', function (RouteBuilder $builder) {
    *     // No $builder->applyMiddleware() here.
    *
    *     // Parse specified extensions from URLs
    *     // $builder->setExtensions(['json', 'xml']);
    *
    *     // Connect API actions here.
    * });
    * ```
    */

    // API access
    $routes->prefix('Api', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json', 'ajax']);

        // Standart RESTful controller API routes
        $builder->resources('Customers', [
            'map' => [
                'customer-points' => [
                    'action' => 'customerPoints',
                    'method' => 'GET',
                ],
            ],
        ]);

        // Bridge controllers for external API integrations
        $builder->resources('AddressesBridge', [
            'only' => [
                'search',
            ],
            'map' => [
                'search' => [
                    'action' => 'search',
                    'method' => 'GET',
                ],
            ],
        ]);
        $builder->resources('AgentBridge', [
            'only' => [
                'ping/{ip_address}',
            ],
            'map' => [
                'ping/{ip_address}' => [
                    'action' => 'ping',
                    'method' => 'GET',
                ],
            ],
        ]);
        $builder->resources('NetworkManagementSystemBridge', [
            'only' => [
                'access-points/{ip_address}',
                'routeros-devices/{ip_address}',
                'ip-address-ranges/{ip_network}',
            ],
            'map' => [
                'access-points/{ip_address}' => [
                    'action' => 'accessPoints',
                    'method' => 'GET',
                ],
                'routeros-devices/{ip_address}' => [
                    'action' => 'routerosDevices',
                    'method' => 'GET',
                ],
                'ip-address-ranges/{ip_network}' => [
                    'action' => 'ipAddressRanges',
                    'method' => 'GET',
                ],
            ],
        ]);
    });

    //the filter reads the request it is generating a URL under, and a console
    //run has none - `Router::getRequest()` answers null there and hands that
    //straight over, so the filter has to take it and step aside. It used to be
    //left unregistered when PHP_SAPI was cli instead, which also left it out
    //under the test runner and built different URLs there than in a browser.
    Router::addUrlFilter(function (array $params, ?ServerRequest $request) {
        if ($request === null) {
            return $params;
        }

        // persistent win-link, unless the caller asked for something else -
        // passing null opts out, for links meant to be followed outside the
        // popup window. Note isset() would not see that null.
        if (
            $request->getQuery('win-link') == 'true'
            && !array_key_exists('win-link', $params['?'] ?? [])
        ) {
            $params['?']['win-link'] = 'true';
        }

        //inject customer_id and contract_id, unless the caller asked for
        //something else - passing null opts out, for links meant to leave
        //the nesting behind. Note isset() would not see that null.
        foreach (['customer_id', 'contract_id'] as $nesting) {
            if ($request->getParam($nesting) && !array_key_exists($nesting, $params)) {
                $params[$nesting] = $request->getParam($nesting);
            }
            if (array_key_exists($nesting, $params) && $params[$nesting] === null) {
                unset($params[$nesting]);
            }
        }

        //remove for self (because of duplicating nesting)
        if (isset($params['controller']) && $params['controller'] == 'Customers') {
            unset($params['customer_id']);
        }
        if (!isset($params['controller']) && $request->getParam('controller') == 'Customers') {
            unset($params['customer_id']);
        }

        if (isset($params['controller']) && $params['controller'] == 'Contracts') {
            unset($params['contract_id']);
        }
        if (!isset($params['controller']) && $request->getParam('controller') == 'Contracts') {
            unset($params['contract_id']);
        }

        return $params;
    });
};
