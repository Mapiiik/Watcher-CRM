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

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

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

$routes->scope('/admin/', function (RouteBuilder $builder) {    

    $builder->setRouteClass(DashedRoute::class);

    $builder->connect('/customers/{customer_id}/contracts/{contract_id}', 'Contracts::view')->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])->setPass(['contract_id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/edit', 'Contracts::edit')->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])->setPass(['contract_id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/delete', 'Contracts::delete')->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+'])->setPass(['contract_id']);

    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}', ['action' => 'index'])->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/add', ['action' => 'add'])->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{id}', ['action' => 'view'])->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+', 'id' => '[0-9]+'])->setPass(['id']);
    $builder->connect('/customers/{customer_id}/contracts/{contract_id}/{controller}/{id}/{action}/*', [])->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+', 'id' => '[0-9]+'])->setPass(['id']);

    $builder->connect('/customers/{customer_id}', 'Customers::view')->setPatterns(['customer_id' => '[0-9]+'])->setPass(['customer_id']);
    $builder->connect('/customers/{customer_id}/edit', 'Customers::edit')->setPatterns(['customer_id' => '[0-9]+'])->setPass(['customer_id']);
    $builder->connect('/customers/{customer_id}/delete', 'Customers::delete')->setPatterns(['customer_id' => '[0-9]+'])->setPass(['customer_id']);
    
    $builder->connect('/customers/{customer_id}/{controller}', ['action' => 'index'])->setPatterns(['customer_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/{controller}/add', ['action' => 'add'])->setPatterns(['customer_id' => '[0-9]+']);
    $builder->connect('/customers/{customer_id}/{controller}/{id}', ['action' => 'view'])->setPatterns(['customer_id' => '[0-9]+', 'id' => '[0-9]+'])->setPass(['id']);
    $builder->connect('/customers/{customer_id}/{controller}/{id}/{action}/*', [])->setPatterns(['customer_id' => '[0-9]+', 'id' => '[0-9]+'])->setPass(['id']);


    $builder->connect('/{controller}', ['action' => 'index']);
    $builder->connect('/{controller}/add', ['action' => 'add']);
    $builder->connect('/{controller}/{id}', ['action' => 'view'])->setPatterns(['id' => '[0-9]+'])->setPass(['id']);
    $builder->connect('/{controller}/{id}/{action}/*', [])->setPatterns(['id' => '[0-9]+'])->setPass(['id']);

    $builder->fallbacks();
});

$routes->scope('/', function (RouteBuilder $builder) {
    /*
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, templates/Pages/home.php)...
     */
    $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

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
//    $builder->fallbacks();
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

use Cake\Routing\Router;
use Cake\Http\ServerRequest;

Router::addUrlFilter(function (array $params, ServerRequest $request) {
    //inject customer_id
    if ($request->getParam('customer_id') && !isset($params['customer_id'])) {
        $params['customer_id'] = $request->getParam('customer_id');
    }
    //inject contract_id
    if ($request->getParam('contract_id') && !isset($params['contract_id'])) {
        $params['contract_id'] = $request->getParam('contract_id');
    }
    
    //remove for self (because of duplicating nesting)
    if (isset($params['controller']) && $params['controller'] == 'Customers') unset ($params['customer_id']);
    if (!isset($params['controller']) && $request->getParam('controller') == 'Customers') unset ($params['customer_id']);

    if (isset($params['controller']) && $params['controller'] == 'Contracts') unset ($params['contract_id']);
    if (!isset($params['controller']) && $request->getParam('controller') == 'Contracts') unset ($params['contract_id']);

    return $params;
});