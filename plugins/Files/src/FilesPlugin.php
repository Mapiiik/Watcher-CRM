<?php
declare(strict_types=1);

namespace Files;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Override;

/**
 * Plugin for Files
 *
 * @psalm-suppress UnusedClass
 */
class FilesPlugin extends BasePlugin
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
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
    }

    /**
     * Add routes for the plugin.
     *
     * The controllers are named for what they answer rather than for the tables they read, so
     * that the plugin and its main table sharing a name does not turn into a path that says it
     * twice. `/files` itself is left alone: the plugin's assets are linked into the webroot under
     * that very name, and the web server answers a path of its own before anything reaches here.
     *
     * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
     * @return void
     */
    #[Override]
    public function routes(RouteBuilder $routes): void
    {
        $routes->plugin(
            'Files',
            ['path' => '/files'],
            function (RouteBuilder $builder): void {
                $builder->fallbacks();
            },
        );
        parent::routes($routes);
    }

    /**
     * Add middleware for the plugin.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    #[Override]
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        // Add your middlewares here

        return $middlewareQueue;
    }
}
