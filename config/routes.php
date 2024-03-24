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

        $builder->resources('Customers', [
            'map' => [
                'customer-points' => [
                    'action' => 'customerPoints',
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

    //apply URL filters only if not called from console
    if (PHP_SAPI !== 'cli') {
        Router::addUrlFilter(function (array $params, ServerRequest $request) {
            // persistent win-link
            if ($request->getQuery('win-link') == 'true') {
                $params['?']['win-link'] = 'true';
            }

            //inject customer_id
            if ($request->getParam('customer_id') && !isset($params['customer_id'])) {
                $params['customer_id'] = $request->getParam('customer_id');
            }
            //inject contract_id
            if ($request->getParam('contract_id') && !isset($params['contract_id'])) {
                $params['contract_id'] = $request->getParam('contract_id');
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
    }
};
