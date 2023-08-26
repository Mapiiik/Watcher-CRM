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
if (!(php_sapi_name() == 'cli')) {
    $url =
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    if (mb_strpos($url, '/legacy/') !== false) {
        header('Location: ' . mb_ereg_replace('/legacy/', '/admin/', $url), true, 303);
        die;
    }
}

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
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 */
/** @var \Cake\Routing\RouteBuilder $routes */
$routes->setRouteClass(DashedRoute::class);

$routes->scope('/admin/', function (RouteBuilder $builder): void {
    $builder->setExtensions(['pdf']);

    /*
    // routes for contract related
    $builder
        ->connect('/customers/{customer_id}/contracts/{action}/{contract_id}/', [
            'controller' => 'Contracts',
        ])
        ->setPatterns([
            'customer_id' => '[0-9]+',
            'contract_id' => '[0-9]+',
        ])
        ->setPass(['contract_id']);

    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{action}/{id}', [])
        ->setPatterns([
            'customer_id' => '[0-9]+',
            'contract_id' => '[0-9]+',
        ]);

    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{action}/', [])
        ->setPatterns([
            'customer_id' => '[0-9]+',
            'contract_id' => '[0-9]+',
        ]);

    // routes for customer related
    $builder
        ->connect('/customers/{action}/{customer_id}', [
            'controller' => 'Customers',
        ])
        ->setPatterns([
            'customer_id' => '[0-9]+',
        ])
        ->setPass(['customer_id']);

    $builder->connect('/customers/{customer_id}/{controller}/{action}/{id}', [])
        ->setPatterns([
            'customer_id' => '[0-9]+',
        ]);

    $builder->connect('/customers/{customer_id}/{controller}/{action}/', [])
        ->setPatterns([
            'customer_id' => '[0-9]+',
        ]);

    // routes
    $builder->connect('/{controller}/{action}/{id}', [])
        ->setPass(['id']);

    $builder->connect('/{controller}/{action}/', []);
    */

    // UUID routes
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{action}/{id}', [])
        ->setPatterns([
            'customer_id' => '[0-9]+',
            'contract_id' => '[0-9]+',
            'id' => '[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12}',
        ])
        ->setPass(['id']);

    $builder->connect('/customers/{customer_id}/{controller}/{action}/{id}', [])
        ->setPatterns([
            'customer_id' => '[0-9]+',
            'id' => '[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12}',
        ])
        ->setPass(['id']);

    $builder->connect('/{controller}/{action}/{id}', [])
        ->setPatterns([
            'id' => '[\w]{8}-[\w]{4}-[\w]{4}-[\w]{4}-[\w]{12}',
        ])
        ->setPass(['id']);

    // other routes
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}', 'Contracts::view')
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])
        ->setPass(['contract_id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/edit', 'Contracts::edit')
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])
        ->setPass(['contract_id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/delete', 'Contracts::delete')
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])
        ->setPass(['contract_id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/print', 'Contracts::print')
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])
        ->setPass(['contract_id']);
    $builder
        ->connect(
            '/customers/{customer_id}/contracts/{contract_id}/terminate-related-billings',
            'Contracts::terminateRelatedBillings'
        )
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])
        ->setPass(['contract_id']);
    $builder
        ->connect(
            '/customers/{customer_id}/contracts/{contract_id}/set-dates-for-related-borrowed-equipments',
            'Contracts::setDatesForRelatedBorrowedEquipments'
        )
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])
        ->setPass(['contract_id']);

    $builder->connect(
        '/contracts/update-all-numbers/*',
        [
            'controller' => 'Contracts',
            'action' => 'updateAllNumbers',
        ]
    );

    $builder->connect(
        '/contracts/update-all-subscriber-verification-codes/*',
        [
            'controller' => 'Contracts',
            'action' => 'updateAllSubscriberVerificationCodes',
        ]
    );

    $builder->connect(
        '/billings/bulk-service-change/*',
        [
            'controller' => 'Billings',
            'action' => 'bulkServiceChange',
        ]
    );

    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}', ['action' => 'index'])
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/add', ['action' => 'add'])
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/add-from-range', [
            'action' => 'addFromRange',
        ])
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{id}', ['action' => 'view'])
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+', 'id' => '[0-9]+'])
        ->setPass(['id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{id}/{action}/*', [])
        ->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+', 'id' => '[0-9]+'])
        ->setPass(['id']);

    $builder->connect('/customers/{customer_id}', 'Customers::view')
        ->setPatterns(['customer_id' => '[0-9]+'])
        ->setPass(['customer_id']);
    $builder->connect('/customers/{customer_id}/edit', 'Customers::edit')
        ->setPatterns(['customer_id' => '[0-9]+'])
        ->setPass(['customer_id']);
    $builder->connect('/customers/{customer_id}/delete', 'Customers::delete')
        ->setPatterns(['customer_id' => '[0-9]+'])
        ->setPass(['customer_id']);
    $builder->connect('/customers/{customer_id}/print', 'Customers::print')
        ->setPatterns(['customer_id' => '[0-9]+'])
        ->setPass(['customer_id']);

    $builder->connect('/customers/{customer_id}/{controller}', ['action' => 'index'])
        ->setPatterns(['customer_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/{controller}/add', ['action' => 'add'])
        ->setPatterns(['customer_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/{controller}/add-from-range', ['action' => 'addFromRange'])
        ->setPatterns(['customer_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/{controller}/{id}', ['action' => 'view'])
        ->setPatterns(['customer_id' => '[0-9]+', 'id' => '[0-9]+'])
        ->setPass(['id']);
    $builder->connect('/customers/{customer_id}/{controller}/{id}/{action}/*', [])
        ->setPatterns(['customer_id' => '[0-9]+', 'id' => '[0-9]+'])
        ->setPass(['id']);

    $builder->connect('/labels/update-related-customer-labels', 'Labels::updateRelatedCustomerLabels');

    $builder->connect('/overviews/{action}/*', ['controller' => 'Overviews'])
        ->setExtensions(['csv']);

    $builder->connect('/{controller}', ['action' => 'index']);
    $builder->connect('/{controller}/add', ['action' => 'add']);
    $builder->connect('/{controller}/add-from-range', ['action' => 'addFromRange']);
    $builder->connect('/{controller}/{id}', ['action' => 'view'])
        ->setPatterns(['id' => '[0-9]+'])
        ->setPass(['id']);
    $builder->connect('/{controller}/{id}/{action}/*', [])
        ->setPatterns(['id' => '[0-9]+'])
        ->setPass(['id']);

    // Default redirect
    $builder->redirect('/', ['controller' => 'Customers', 'action' => 'index'], ['status' => 303]);

    //$builder->fallbacks();
});

$routes->scope('/', function (RouteBuilder $builder): void {
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
    //$builder->fallbacks();
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
    $builder->setExtensions(['json']);

    $builder->resources('Customers', [
        'map' => [
            'customer-points' => [
                'action' => 'customerPoints',
                'method' => 'GET',
            ],
        ],
    ]);
});

//apply URL filters only if not called from console
if (!(php_sapi_name() == 'cli')) {
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
