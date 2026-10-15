<?php
/**
 * How people sign in: read by this plugin, and by CakeDC/Auth, which lends it the second factor,
 * the permissions and the services of other identity providers.
 *
 * Every application carrying this plugin reads the same file. What differs between installations
 * comes from the environment.
 */

use Access\Authentication\Argon2idPasswordHasher;
use Access\Authentication\FormAuthenticator;
use Access\Authentication\TwoFactorChecker;
use Access\Middleware\UnauthorizedHandler\RedirectHandler;
use Cake\Core\Configure;
use Cake\Routing\Router;
use CakeDC\Auth\Authentication\TwoFactorProcessor\OneTimePasswordProcessor;
use CakeDC\Auth\Authenticator\SocialAuthenticator;
use CakeDC\Auth\Social\Mapper\Google as GoogleMapper;
use CakeDC\Auth\Social\Service\OAuth2Service;
use League\OAuth2\Client\Provider\Google;

$users = ['plugin' => false, 'prefix' => false, 'controller' => 'Users'];
$login = $users + ['action' => 'login'];
// accounts that are not active do not sign in, whichever way they try
$resolver = ['className' => 'Authentication.Orm', 'userModel' => 'Users', 'finder' => 'active'];
$base = Router::fullBaseUrl();

return [
    'Users' => [
        // the application's own classes, which the permissions and the templates name
        'controller' => 'Users',
        'table' => 'Users',
        'Social' => [
            // signing in with Google is offered where the installation has a client for it
            'login' => (bool)env('GOOGLE_OAUTH_CLIENT_ID', false),
        ],
        'Superuser' => [
            'allowedToChangeSettings' => true,
        ],
    ],
    'Auth' => [
        'AuthenticationComponent' => [
            'loginAction' => $login,
            'logoutRedirect' => $login,
            // The root, which is where the page a user starts on is decided - the dashboard
            // unless they have settled on another one for themselves. Naming a page here instead
            // would put that choice out of reach of everybody who arrives by signing in.
            'loginRedirect' => '/',
            'requireIdentity' => false,
        ],
        // A cookie that remembers somebody and a link sent by e-mail are left out: each signs in
        // without the password and without the second factor.
        'Authenticators' => [
            'Session' => [
                'className' => 'Authentication.Session',
                'skipTwoFactorVerify' => true,
                'sessionKey' => 'Auth',
            ],
            'Form' => [
                'className' => FormAuthenticator::class,
                'urlChecker' => 'Authentication.CakeRouter',
                'loginUrl' => $login,
                'identifier' => [
                    'Authentication.Password' => [
                        'fields' => ['username' => ['username', 'email'], 'password' => 'password'],
                        // the only look-up that reads the password, to check it
                        'resolver' => ['finder' => 'signIn'] + $resolver,
                        // Argon2id for new passwords, and for old ones the next time their owner
                        // signs in
                        'passwordHasher' => Argon2idPasswordHasher::class,
                    ],
                ],
            ],
            // API clients send their key as a Bearer token, never in the address, which ends up
            // in the logs
            'Token' => [
                'className' => 'Authentication.Token',
                'skipTwoFactorVerify' => true,
                'header' => 'Authorization',
                'tokenPrefix' => 'Bearer',
                'identifier' => [
                    'Authentication.Token' => [
                        'tokenField' => 'api_token',
                        // only the hash of a key is kept
                        'hashAlgorithm' => 'sha256',
                        'resolver' => $resolver,
                    ],
                ],
            ],
            'Social' => [
                'className' => SocialAuthenticator::class,
                'identifier' => [
                    'Access.LinkedSocial' => ['resolver' => $resolver],
                ],
            ],
        ],
        'AuthorizationMiddleware' => [
            'unauthorizedHandler' => [
                'className' => RedirectHandler::class,
                'url' => $login,
            ],
        ],
        'AuthorizationComponent' => [
            'enabled' => true,
        ],
        'RbacPolicy' => [],
    ],
    // one-time codes from an authenticator app, asked for where the settings or the account say so
    'OneTimePasswordAuthenticator' => [
        'login' => true,
        'checker' => TwoFactorChecker::class,
        'verifyAction' => $users + ['action' => 'verify'],
        // what the authenticator app calls the application
        'issuer' => parse_url((string)Configure::read('App.fullBaseUrl'), PHP_URL_HOST) ?: env('APP_NAME', 'Watcher'),
    ],
    // security keys and passkeys are left to the identity provider
    'TwoFactorProcessors' => [
        OneTimePasswordProcessor::class,
    ],
    'OAuth' => [
        'path' => $users + ['action' => 'socialLogin'],
        'providers' => [
            'google' => [
                'service' => OAuth2Service::class,
                'className' => Google::class,
                'mapper' => GoogleMapper::class,
                'options' => [
                    'clientId' => env('GOOGLE_OAUTH_CLIENT_ID', null),
                    'clientSecret' => env('GOOGLE_OAUTH_CLIENT_SECRET', null),
                    'redirectUri' => $base . '/auth/google',
                    'linkSocialUri' => $base . '/link-social/google',
                    'callbackLinkSocialUri' => $base . '/callback-link-social/google',
                ],
            ],
        ],
    ],
];
