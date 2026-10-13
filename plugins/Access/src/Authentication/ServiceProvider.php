<?php
declare(strict_types=1);

namespace Access\Authentication;

use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Policy\MapResolver;
use Authorization\Policy\OrmResolver;
use Authorization\Policy\ResolverCollection;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use CakeDC\Auth\Authentication\AuthenticationService;
use CakeDC\Auth\Authentication\TwoFactorProcessorLoader;
use CakeDC\Auth\Authenticator\TwoFactorAuthenticator;
use CakeDC\Auth\Policy\CollectionPolicy;
use CakeDC\Auth\Policy\RbacPolicy;
use CakeDC\Auth\Policy\SuperuserPolicy;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Who is asking and what they may have, as the configuration of this plugin says.
 *
 * A way of signing in that is not exempt from the second factor hands the account over to it
 * instead of signing it in. What the code then confirms is signed in by the last authenticator.
 * Permissions come from `config/permissions.php`, and a superuser has them all.
 */
class ServiceProvider implements AuthenticationServiceProviderInterface, AuthorizationServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService(['processors' => TwoFactorProcessorLoader::processors()]);
        foreach ((array)Configure::read('Auth.Authenticators') as $name => $options) {
            $service->loadAuthenticator((string)$name, (array)$options);
        }
        $service->loadAuthenticator('TwoFactor', [
            'className' => TwoFactorAuthenticator::class,
            'skipTwoFactorVerify' => true,
        ]);

        return $service;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $requests = new MapResolver();
        $requests->map(ServerRequest::class, new CollectionPolicy([
            SuperuserPolicy::class,
            new RbacPolicy((array)Configure::read('Auth.RbacPolicy')),
        ]));

        return new AuthorizationService(new ResolverCollection([$requests, new OrmResolver()]));
    }
}
