<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Controller\UsersController;
use Access\Identifier\LinkedSocialIdentifier;
use Access\Social\Service\OidcService;
use Access\Test\TestCase\AccountsTrait;
use Access\Test\TestCase\OidcProviderTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Log\Engine\ArrayLog;
use Cake\Log\Log;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Service\SettingsService;
use Settings\Utility\Settings;

/**
 * Signing in through an OpenID Connect provider, from the button to the account.
 */
#[UsesClass(UsersController::class)]
#[UsesClass(OidcService::class)]
class OidcSignInTest extends TestCase
{
    use AccountsTrait;
    use IntegrationTestTrait;
    use OidcProviderTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Settings.Settings',
    ];

    /**
     * The environment an installation with an OpenID Connect provider has.
     *
     * @var array<string, string>
     */
    private const ENVIRONMENT = [
        'OIDC_ISSUER' => 'https://idp.example.com/application/o/watcher/',
        'OIDC_CLIENT_ID' => 'test-client',
        'OIDC_CLIENT_SECRET' => 'test-secret',
        // none, so that the button says what it says by default, whatever the working copy's own
        // .env names
        'OIDC_LABEL' => '',
    ];

    /**
     * The environment as it was before the test: what `$_SERVER`, `$_ENV` and `getenv()` held.
     *
     * @var array<string, array{0: mixed, 1: mixed, 2: string|false}>
     */
    private array $environmentBefore = [];

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENVIRONMENT as $name => $value) {
            $this->environmentBefore[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
            $set = $value === '' ? null : $value;
            $this->setEnvironment($name, $set, $set, $set ?? false);
        }
        Cache::clear('default');
        Cache::clear(LoginThrottle::CACHE);
        Log::drop('auth');
        Log::setConfig('auth', ['className' => ArrayLog::class, 'scopes' => ['auth']]);
        $this->getTableLocator()->get('Access.SocialAccounts')->deleteAll(['1 = 1']);
        $this->mockOidcProvider();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        foreach ($this->environmentBefore as $name => [$server, $env, $process]) {
            $this->setEnvironment($name, $server, $env, $process);
        }
        Log::drop('auth');
        Cache::clear(LoginThrottle::CACHE);
        Cache::clear('default');
        Configure::delete('OAuth.providers.oidc.collaborators');
        // the settings are kept in memory too, so the next test reads them again
        Settings::setInstance(new SettingsService());

        parent::tearDown();
    }

    /**
     * An identity linked to an account signs it in, and where the provider asked for a second
     * factor the account is not asked again, even where it has to use one.
     *
     * @return void
     */
    public function testALinkedIdentitySignsInWithTheSecondFactorOfTheProvider(): void
    {
        Settings::set('access.security.two_step_verification_required', true);
        $this->linkedAccount();

        $this->get('/login');
        $this->assertResponseContains('href="/auth/oidc"');
        $this->assertResponseContains('Sign in with Single Sign-On');

        $this->comeBackFromTheProvider($this->startSigningIn());

        $this->assertRedirect('/');
        $signedIn = $_SESSION['Auth'] ?? null;
        $this->assertInstanceOf(EntityInterface::class, $signedIn);
        $this->assertSame('jnovak', $signedIn->get('username'));
        $this->assertNotEmpty($signedIn->get(LinkedSocialIdentifier::ID_TOKEN));
        $this->assertStringContainsString(
            'signed in user="jnovak"',
            $this->authLog(),
        );
        $this->assertStringContainsString('with="SocialAuthenticator" methods="pwd+mfa"', $this->authLog());
    }

    /**
     * Where the provider did not ask for a second factor, the rules of the application decide:
     * an account that has to use one is asked for it, others are let in.
     *
     * @return void
     */
    public function testWithoutASecondFactorAtTheProviderTheApplicationsRulesDecide(): void
    {
        $this->linkedAccount();

        $this->comeBackFromTheProvider($this->startSigningIn(), ['amr' => ['pwd']]);
        $this->assertRedirect('/');
        $this->assertStringContainsString(
            'no second factor at provider user="jnovak" provider="oidc" methods="pwd"',
            $this->authLog(),
        );

        $this->_session = [];
        Settings::set('access.security.two_step_verification_required', true);
        $this->comeBackFromTheProvider($this->startSigningIn(), ['amr' => ['pwd']]);
        $this->assertRedirectContains('/verify');
    }

    /**
     * An identity nobody linked signs nobody in, and says so.
     *
     * @return void
     */
    public function testAnIdentityNobodyLinkedSignsNobodyIn(): void
    {
        $this->addAccount();

        $this->comeBackFromTheProvider($this->startSigningIn());

        $this->assertRedirect('/login');
        $this->assertFlashMessage(
            'No account here is linked to that identity. Sign in with your password and link it in your profile.',
            'auth',
        );
        $this->assertNull($this->_requestSession?->read('Auth'));
        $this->assertStringContainsString('social account not linked provider="oidc"', $this->authLog());
    }

    /**
     * A sign-in the provider refused or could not finish says so, rather than that nobody is linked.
     *
     * @return void
     */
    public function testASignInTheProviderDidNotFinishSaysSo(): void
    {
        $this->linkedAccount();
        $state = $this->startSigningIn();

        $this->get('/auth/oidc?error=access_denied&state=' . $state);

        $this->assertRedirect('/login');
        $this->assertFlashMessage('Signing in with Single Sign-On did not finish. Please try again.', 'auth');

        $this->cleanupMockResponses();
        Cache::clear('default');
        $this->mockClientGet(self::oidcIssuer() . '.well-known/openid-configuration', $this->newClientResponse(503));
        $this->get('/auth/oidc');
        $this->assertRedirect('http://localhost/auth/oidc?error=temporarily_unavailable');
        $this->assertStringContainsString('identity provider unavailable provider="oidc"', $this->authLog());
    }

    /**
     * Signing out of an account signed in through the provider ends its session with the
     * application there too.
     *
     * @return void
     */
    public function testSigningOutEndsTheSessionAtTheProvider(): void
    {
        $this->linkedAccount();
        $this->comeBackFromTheProvider($this->startSigningIn());
        $this->_session = $_SESSION ?? [];

        $this->get('/logout');

        $this->assertRedirectContains(self::oidcIssuer() . 'end-session/?id_token_hint=');
        $this->assertRedirectContains('post_logout_redirect_uri=' . urlencode('http://localhost/login'));
    }

    /**
     * The owner of an account links their identity at the provider from the profile.
     *
     * @return void
     */
    public function testAnIdentityIsLinkedFromTheProfile(): void
    {
        $user = $this->addAccount();
        $this->session(['Auth' => $user]);
        $this->get('/profile');
        $this->assertResponseContains('href="/link-social/oidc"');

        $this->get('/link-social/oidc');
        $this->assertRedirectContains('redirect_uri=' . urlencode('http://localhost/callback-link-social/oidc'));
        $this->_session = $_SESSION ?? [];
        Configure::write(
            'OAuth.providers.oidc.collaborators.httpClient',
            $this->oidcTokenEndpoint($this->oidcIdToken(['nonce' => $this->pending('nonce')])),
        );
        $this->get('/callback-link-social/oidc?code=the-code&state=' . $this->pending('state'));

        $this->assertRedirect('/profile');
        $this->assertTrue($this->getTableLocator()->get('Access.SocialAccounts')->exists([
            'user_id' => $user->get('id'),
            'provider' => 'oidc',
            'reference' => 'person-at-the-provider',
        ]));
    }

    /**
     * Where a password is not right, everybody is told that an account without one signs in
     * through the provider, so that nobody learns which accounts have none.
     *
     * @return void
     */
    public function testAWrongPasswordPointsToTheProvider(): void
    {
        $this->addAccount();

        // the sign-in page shows the message at once, which takes it out of the session
        $this->enableRetainFlashMessages();
        $this->enableCsrfToken();
        $this->post('/login', ['username' => 'jnovak', 'password' => 'not the one']);

        $this->assertFlashMessage(
            'The username or the password is not right. An account without a password signs in with Single Sign-On.',
            'auth',
        );
    }

    /**
     * Sets a variable of the environment wherever `env()` looks for it, or takes it away.
     *
     * @param string $name The variable.
     * @param mixed $server In `$_SERVER`, null for none.
     * @param mixed $env In `$_ENV`, null for none.
     * @param string|false $process For `getenv()`, false for none.
     * @return void
     */
    private function setEnvironment(string $name, mixed $server, mixed $env, string|false $process): void
    {
        if ($server === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $server;
        }
        if ($env === null) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $env;
        }
        putenv($process === false ? $name : $name . '=' . $process);
    }

    /**
     * Starts signing in with the button, and says the state the provider is to send back.
     *
     * @return string
     */
    private function startSigningIn(): string
    {
        $this->get('/auth/oidc');
        $this->assertRedirectContains('https://idp.example.com/application/o/authorize/?');
        $this->_session = $_SESSION ?? [];

        return $this->pending('state');
    }

    /**
     * What the sign-in waiting for the provider keeps in the session carried to the next request.
     *
     * Read from what is carried, as asking the request's session starts it again, which on the
     * command line empties it.
     *
     * @param string $key The state, the nonce or the verifier.
     * @return string
     */
    private function pending(string $key): string
    {
        return (string)($this->_session['Access']['oidc'][$key] ?? '');
    }

    /**
     * Comes back from the provider with a code, which its token endpoint answers with an ID token.
     *
     * @param string $state The state the sign-in was started with.
     * @param array<string, mixed> $claims Claims of the ID token in place of the usual ones.
     * @return void
     */
    private function comeBackFromTheProvider(string $state, array $claims = []): void
    {
        Configure::write(
            'OAuth.providers.oidc.collaborators.httpClient',
            $this->oidcTokenEndpoint($this->oidcIdToken($claims + ['nonce' => $this->pending('nonce')])),
        );
        $this->get('/auth/oidc?code=the-code&state=' . $state);
    }

    /**
     * Adds an account whose identity at the provider is linked to it.
     *
     * @return void
     */
    private function linkedAccount(): void
    {
        $user = $this->addAccount();
        $this->getTableLocator()->get('Access.SocialAccounts')
            ->link((string)$user->get('id'), 'oidc', 'person-at-the-provider', 'jnovak');
    }

    /**
     * What the sign-in log holds.
     *
     * @return string
     */
    private function authLog(): string
    {
        /** @var \Cake\Log\Engine\ArrayLog $log */
        $log = Log::engine('auth');

        return implode("\n", $log->read());
    }
}
