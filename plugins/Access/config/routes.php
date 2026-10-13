<?php
/**
 * The addresses of signing in and of the accounts, the same in every application carrying this
 * plugin. They lead to the application's own controller.
 */

use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->scope('/', function (RouteBuilder $builder): void {
        $users = ['controller' => (string)Configure::read('Users.controller')];

        $builder->connect('/login', $users + ['action' => 'login']);
        $builder->connect('/logout', $users + ['action' => 'logout']);
        $builder->connect('/verify', $users + ['action' => 'verify']);
        $builder->connect('/profile', $users + ['action' => 'profile']);

        // signing in with another provider, and tying an account there to one here
        $builder->connect('/auth/{provider}', $users + ['action' => 'socialLogin'], ['pass' => ['provider']]);
        $builder->connect('/link-social/{provider}', $users + ['action' => 'linkSocial'], ['pass' => ['provider']]);
        $builder->connect(
            '/callback-link-social/{provider}',
            $users + ['action' => 'callbackLinkSocial'],
            ['pass' => ['provider']],
        );

        $builder->connect('/users/{action}/*', $users);
    });
};
