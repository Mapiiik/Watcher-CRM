<?php
declare(strict_types=1);

namespace Radius;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for RADIUS
 */
class RadiusPlugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
    }

    /**
     * Add routes for the plugin.
     *
     * If your plugin has many routes and you would like to isolate them into a separate file,
     * you can create `$plugin/config/routes.php` and delete this method.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Radius',
            ['path' => '/radius'],
            function (RouteBuilder $builder): void {
                $builder->connect(
                    '/customers/{customer_id}/contracts/{contract_id}/{controller}',
                    ['action' => 'index']
                )->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);

                $builder->connect(
                    '/customers/{customer_id}/contracts/{contract_id}/{controller}/{action}/*',
                    []
                )->setPatterns(['customer_id' => '[0-9]+', 'contract_id' => '[0-9]+']);

                $builder->connect(
                    '/customers/{customer_id}/{controller}',
                    ['action' => 'index']
                )->setPatterns(['customer_id' => '[0-9]+']);

                $builder->connect(
                    '/customers/{customer_id}/{controller}/{action}/*',
                    []
                )->setPatterns(['customer_id' => '[0-9]+', 'id' => '[0-9]+']);

                $builder->fallbacks();
            }
        );
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Add your middlewares here

        return $middlewareQueue;
    }
}
