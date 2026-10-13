<?php
declare(strict_types=1);

namespace Access;

use Access\Authentication\AuthLog;
use Access\Authentication\LoginThrottle;
use Access\Authentication\ServiceProvider;
use Access\Social\Providers;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Middleware\RequestAuthorizationMiddleware;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\Log\Engine\SyslogLog;
use Cake\Log\Log;
use CakeDC\Auth\Middleware\SocialAuthMiddleware;
use CakeDC\Auth\Middleware\TwoFactorMiddleware;
use Override;

/**
 * Plugin for Access
 */
class AccessPlugin extends BasePlugin
{
    /**
     * Reads how people sign in, and sets up the log and the cache it writes to where the
     * application has not.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        Configure::load('Access.users');
        if (!Configure::read('Users.Social.login')) {
            Configure::delete('Auth.Authenticators.Social');
        }

        if (!in_array('auth', Log::configured(), true)) {
            Log::setConfig('auth', [
                'className' => SyslogLog::class,
                'scopes' => [AuthLog::SCOPE],
                'facility' => LOG_AUTH,
                'prefix' => 'watcher-' . env('APP_NAME', 'app'),
            ]);
        }
        if (!in_array(LoginThrottle::CACHE, Cache::configured(), true)) {
            Cache::setConfig(LoginThrottle::CACHE, [
                'className' => FileEngine::class,
                'prefix' => 'login_throttle_',
                'path' => CACHE . 'login_throttle' . DS,
                'duration' => '+1 day',
            ]);
        }
    }

    /**
     * Who is asking, whether they still owe a one-time code, and whether they may have what they
     * ask for - in this order, after the application's own middleware.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to update.
     * @return \Cake\Http\MiddlewareQueue
     */
    #[Override]
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        if (Configure::read('Users.Social.login')) {
            // sends somebody off to the provider, and takes the answer it sends them back with
            $middlewareQueue->add(new SocialAuthMiddleware([
                'urlChecker' => 'Authentication.CakeRouter',
                'loginUrl' => array_map(
                    fn(string $provider): array => (array)Configure::read('OAuth.path') + ['provider' => $provider],
                    Providers::names(),
                ),
            ]));
        }

        $services = new ServiceProvider();

        return $middlewareQueue
            ->add(new AuthenticationMiddleware($services))
            ->add(new TwoFactorMiddleware())
            ->add(new AuthorizationMiddleware($services, (array)Configure::read('Auth.AuthorizationMiddleware')))
            ->add(new RequestAuthorizationMiddleware());
    }
}
