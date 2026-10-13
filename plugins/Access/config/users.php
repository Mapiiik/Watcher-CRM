<?php
/**
 * How people sign in, read by CakeDC/Users over its own defaults.
 *
 * Every application carrying this plugin reads the same file. What differs between installations
 * comes from the environment.
 */

use Access\Authentication\Argon2idPasswordHasher;
use Access\Authentication\FormAuthenticator;
use Access\Authentication\TwoFactorChecker;
use Cake\Core\Configure;

return [
    'Users.controller' => 'AppUsers',
    'Users.table' => 'AppUsers',
    // Argon2id for new passwords, and for old ones the next time their owner signs in. Both have
    // to name the same hasher, or every sign-in would hash the password again.
    'Users.passwordHasher' => Argon2idPasswordHasher::class,
    'Auth.Authenticators.Form.className' => FormAuthenticator::class,
    'Auth.Authenticators.Form.identifier' => [
        'Authentication.Password' => ['passwordHasher' => Argon2idPasswordHasher::class],
    ],
    // the identifier is registered under its name without the plugin
    'Auth.PasswordRehash.authenticators' => ['Form' => 'Password'],
    'Users.Registration.active' => false,
    // one-time codes from an authenticator app, asked for where the settings or the account say so
    'OneTimePasswordAuthenticator.login' => true,
    'OneTimePasswordAuthenticator.checker' => TwoFactorChecker::class,
    // what the authenticator app calls the application
    'OneTimePasswordAuthenticator.issuer' => parse_url((string)Configure::read('App.fullBaseUrl'), PHP_URL_HOST)
        ?: env('APP_NAME', 'Watcher'),
    // security keys and passkeys are left to the identity provider
    'Webauthn2fa.enabled' => false,
    // signing in again after eight hours idle is enough; a cookie that signs in without the
    // password, or a link sent by e-mail, would also skip the second factor
    'Users.RememberMe.active' => false,
    'OneTimeLogin.enabled' => false,
    'Users.Superuser.allowedToChangePasswords' => true,
    'Users.Superuser.allowedToChangeSettings' => true,
    // a profile shows what the account is, so only its owner opens it
    'Users.Profile.viewOthers' => false,
    // signing in with Google is offered where the installation has a client for it
    'Users.Social.login' => (bool)env('GOOGLE_OAUTH_CLIENT_ID', false),
    // API clients may send their key as a Bearer token rather than in the address
    'Auth.Authenticators.Token.header' => 'Authorization',
    'Auth.Authenticators.Token.tokenPrefix' => 'Bearer',
    // The root, which is where the page a user starts on is decided - the dashboard unless
    // they have settled on another one for themselves. Naming a page here instead would put
    // that choice out of reach of everybody who arrives by signing in.
    'Auth.AuthenticationComponent.loginRedirect' => '/',
    'OAuth.providers.google.options.clientId' => env('GOOGLE_OAUTH_CLIENT_ID', null),
    'OAuth.providers.google.options.clientSecret' => env('GOOGLE_OAUTH_CLIENT_SECRET', null),
];
