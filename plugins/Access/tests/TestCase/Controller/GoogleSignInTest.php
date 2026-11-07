<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Controller;

use Access\Authentication\LoginThrottle;
use Access\Controller\UsersController;
use Access\Social\OpenIdConfiguration;
use Access\Social\Service\OidcService;
use Access\Test\TestCase\AccountsTrait;
use Access\Test\TestCase\OidcProviderTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Signing in with Google, which goes the way of any other OpenID Connect provider, beside one such
 * provider of the installation's own.
 */
#[UsesClass(UsersController::class)]
#[UsesClass(OidcService::class)]
class GoogleSignInTest extends TestCase
{
    use AccountsTrait;
    use IntegrationTestTrait;
    use OidcProviderTrait;

    private const ISSUER = 'https://accounts.google.com';

    private const CLIENT = 'test-client.apps.googleusercontent.com';

    /**
     * The environment of an installation with both.
     *
     * @var array<string, string>
     */
    private const ENVIRONMENT = [
        'GOOGLE_OAUTH_CLIENT_ID' => self::CLIENT,
        'GOOGLE_OAUTH_CLIENT_SECRET' => 'test-secret',
        'OIDC_ISSUER' => 'https://idp.example.com/application/o/watcher/',
        'OIDC_CLIENT_ID' => 'test-client',
        'OIDC_CLIENT_SECRET' => 'test-secret',
    ];

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
            $this->setEnvironment($name, $value, $value, $value);
        }
        Cache::clear('default');
        Cache::clear(LoginThrottle::CACHE);
        $this->getTableLocator()->get('Access.SocialAccounts')->deleteAll(['1 = 1']);
        $this->mockOidcProvider();
        $this->mockGoogle();
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
        Cache::clear(LoginThrottle::CACHE);
        Cache::clear('default');
        Cache::clear(OpenIdConfiguration::CACHE);
        Configure::delete('OAuth.providers.google.collaborators');

        parent::tearDown();
    }

    /**
     * Somebody with an identity at Google linked to their account signs in with it, its ID token
     * saying nothing of a username or of how they signed in there.
     *
     * @return void
     */
    public function testALinkedIdentitySignsIn(): void
    {
        $this->linkedAccount();

        $this->signInWithGoogle();

        $this->assertSame('jnovak', $_SESSION['Auth']['username'] ?? null);
    }

    /**
     * The person is asked with a state, a nonce and a PKCE challenge.
     *
     * @return void
     */
    public function testASignInStartsWithAllItIsHeldToLater(): void
    {
        $this->get('/auth/google');

        $this->assertRedirectContains(self::ISSUER . '/o/oauth2/v2/auth?');
        $this->assertRedirectContains('state=');
        $this->assertRedirectContains('nonce=');
        $this->assertRedirectContains('code_challenge=');
        $this->assertRedirectContains('code_challenge_method=S256');
        $this->assertRedirectContains('scope=openid');
    }

    /**
     * An address with a code of somebody else's, sent to whoever is signed in, links nothing: there
     * is no sign-in of theirs for it to answer.
     *
     * @return void
     */
    public function testACodeThatNoSignInAskedForLinksNothing(): void
    {
        $user = $this->addAccount();
        $this->session(['Auth' => $user]);
        $tokenEndpoint = new MockHandler([new GuzzleResponse(200)]);
        Configure::write(
            'OAuth.providers.google.collaborators.httpClient',
            new GuzzleClient(['handler' => HandlerStack::create($tokenEndpoint)]),
        );

        $this->get('/callback-link-social/google?code=the-code-of-somebody-else');

        $this->assertRedirect('/profile');
        $this->assertCount(1, $tokenEndpoint, 'The code went to be exchanged.');
        $this->assertFalse($this->getTableLocator()->get('Access.SocialAccounts')->exists(['provider' => 'google']));
    }

    /**
     * Signing out after Google stays here. It has no such address, and the ID token is not for the
     * other provider to see.
     *
     * @return void
     */
    public function testSigningOutGoesToNoProvider(): void
    {
        $this->linkedAccount();
        $this->signInWithGoogle();
        $this->_session = $_SESSION ?? [];

        $this->get('/logout');

        $this->assertRedirect('/login');
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
     * Answers what Google publishes, which names no address to end a session at.
     *
     * @return void
     */
    private function mockGoogle(): void
    {
        $this->mockClientGet(
            self::ISSUER . '/.well-known/openid-configuration',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'issuer' => self::ISSUER,
                'authorization_endpoint' => self::ISSUER . '/o/oauth2/v2/auth',
                'token_endpoint' => 'https://oauth2.googleapis.com/token',
                'userinfo_endpoint' => 'https://openidconnect.googleapis.com/v1/userinfo',
                'jwks_uri' => 'https://www.googleapis.com/oauth2/v3/certs',
            ])),
        );
        $this->mockClientGet(
            'https://www.googleapis.com/oauth2/v3/certs',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'keys' => [$this->oidcPublicKey('key-1')],
            ])),
        );
    }

    /**
     * An ID token as Google signs it: no username and no word on how the person signed in.
     *
     * @param string $nonce The nonce the sign-in was started with.
     * @return string
     */
    private function googleIdToken(string $nonce): string
    {
        return $this->oidcIdToken([
            'iss' => self::ISSUER,
            'aud' => self::CLIENT,
            'nonce' => $nonce,
            'amr' => null,
            'preferred_username' => null,
            'name' => 'Jan Novak',
            'given_name' => 'Jan',
            'family_name' => 'Novak',
            'email_verified' => true,
        ]);
    }

    /**
     * Goes to Google and comes back with a code its token endpoint answers.
     *
     * @return void
     */
    private function signInWithGoogle(): void
    {
        $this->get('/auth/google');
        $this->_session = $_SESSION ?? [];
        $pending = (array)($this->_session['Access']['oidc'] ?? []);
        Configure::write(
            'OAuth.providers.google.collaborators.httpClient',
            $this->oidcTokenEndpoint($this->googleIdToken((string)($pending['nonce'] ?? ''))),
        );

        $this->get('/auth/google?code=the-code&state=' . ($pending['state'] ?? ''));
    }

    /**
     * Adds an account whose identity at Google is linked to it.
     *
     * @return void
     */
    private function linkedAccount(): void
    {
        $user = $this->addAccount();
        $this->getTableLocator()->get('Access.SocialAccounts')
            ->link((string)$user->get('id'), 'google', 'person-at-the-provider', 'jan.novak@example.com');
    }
}
