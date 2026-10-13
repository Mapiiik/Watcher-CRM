<?php
declare(strict_types=1);

namespace Access;

use Access\Authentication\Argon2idPasswordHasher;
use Access\Authentication\AuthLog;
use Access\Authentication\LoginThrottle;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Log\Engine\SyslogLog;
use Cake\Log\Log;
use Override;

/**
 * Plugin for Access
 */
class AccessPlugin extends BasePlugin
{
    /**
     * Ways of signing in that skip the second factor, or that nobody here uses. A cookie that
     * remembers somebody and a link sent by e-mail each sign in without a password and without a
     * one-time code; an e-mail asked for after signing in with another service only matters to
     * registering.
     *
     * @var list<string>
     */
    private const UNUSED_AUTHENTICATORS = ['Cookie', 'OneTimeToken', 'SocialPendingEmail'];

    /**
     * Takes out of the CakeDC/Users configuration what this plugin's own file cannot, and sets up
     * the log and the cache it writes to where the application has not.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        // the configuration is merged into CakeDC's, so leaving something out has to be done here
        foreach (self::UNUSED_AUTHENTICATORS as $authenticator) {
            Configure::delete('Auth.Authenticators.' . $authenticator);
        }
        if (!Configure::read('Users.Social.login')) {
            Configure::delete('Auth.Authenticators.Social');
        }

        // An identifier is named with a dot, and a config file writing below it would replace what
        // CakeDC set there - the fields, and the finder that keeps inactive accounts out - so it is
        // adjusted here instead. Passwords are checked with the hasher they are stored with, and
        // API keys are looked up by the hash that is all there is of them.
        $form = (array)Configure::read('Auth.Authenticators.Form.identifier');
        $form['Authentication.Password']['passwordHasher'] = Argon2idPasswordHasher::class;
        Configure::write('Auth.Authenticators.Form.identifier', $form);
        $token = (array)Configure::read('Auth.Authenticators.Token.identifier');
        $token['Authentication.Token']['hashAlgorithm'] = 'sha256';
        Configure::write('Auth.Authenticators.Token.identifier', $token);

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
}
