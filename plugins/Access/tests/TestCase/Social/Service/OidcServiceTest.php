<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Social\Service;

use Access\Social\Mapper\Oidc;
use Access\Social\OpenIdConfiguration;
use Access\Social\Service\OidcService;
use Access\Test\TestCase\OidcProviderTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use League\OAuth2\Client\Provider\GenericProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;

/**
 * Access\Social\Service\OidcService Test Case
 */
#[UsesClass(OidcService::class)]
class OidcServiceTest extends TestCase
{
    use OidcProviderTrait;

    /**
     * Signing in starts with PKCE, a state and a nonce, remembered for when the person comes back.
     *
     * @return void
     */
    public function testSigningInStartsWithPkceStateAndNonce(): void
    {
        $this->mockOidcProvider();
        $request = $this->request();

        $url = $this->service()->getAuthorizationUrl($request);

        $this->assertStringStartsWith('https://idp.example.com/application/o/authorize/?', $url);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $pending = (array)$request->getSession()->read(OidcService::SESSION_KEY);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame('openid profile email', $query['scope']);
        $this->assertSame($pending['state'], $query['state']);
        $this->assertSame($pending['nonce'], $query['nonce']);
        $this->assertSame(
            rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '='),
            $query['code_challenge'],
        );
    }

    /**
     * Where the provider cannot be asked, the person is sent straight back with an error.
     *
     * @return void
     */
    public function testAProviderThatDoesNotAnswerSendsStraightBack(): void
    {
        $this->cleanupMockResponses();
        Cache::clear(OpenIdConfiguration::CACHE);
        $this->mockClientGet(self::oidcIssuer() . '.well-known/openid-configuration', $this->newClientResponse(503));

        $url = $this->service()->getAuthorizationUrl($this->request());

        $this->assertSame('http://localhost/auth/oidc?error=temporarily_unavailable', $url);
    }

    /**
     * The person who comes back is read from the ID token, once it holds, with what the provider
     * said of the second factor; what was remembered is used up.
     *
     * @return void
     */
    public function testThePersonComesFromTheIdToken(): void
    {
        $this->mockOidcProvider();
        $request = $this->request(['code' => 'the-code', 'state' => 'the-state']);
        $request->getSession()->write(OidcService::SESSION_KEY, [
            'state' => 'the-state',
            'nonce' => 'the-nonce',
            'verifier' => 'the-verifier',
        ]);
        $idToken = $this->oidcIdToken();

        $service = $this->service($this->oidcTokenEndpoint($idToken));
        $this->assertTrue($service->isGetUserStep($request));
        $user = $service->getUser($request);

        $this->assertSame('person-at-the-provider', $user['sub']);
        $this->assertSame($idToken, $user['id_token']);
        $this->assertTrue($user['second_factor_at_provider']);
        $this->assertNull($request->getSession()->read(OidcService::SESSION_KEY));

        $identity = (new Oidc())($user);
        $this->assertSame('person-at-the-provider', $identity['id']);
        $this->assertSame(['pwd', 'mfa'], $identity['methods']);
        $this->assertTrue($identity['second_factor_at_provider']);
    }

    /**
     * An answer to another sign-in, one the provider ended with an error, or a token for another
     * sign-in is refused.
     *
     * @return void
     */
    public function testAnAnswerThatIsNotThisSignInIsRefused(): void
    {
        $this->mockOidcProvider();
        $pending = ['state' => 'the-state', 'nonce' => 'the-nonce', 'verifier' => 'the-verifier'];

        $request = $this->request(['code' => 'the-code', 'state' => 'another-state']);
        $request->getSession()->write(OidcService::SESSION_KEY, $pending);
        try {
            $this->service()->getUser($request);
            $this->fail('Another state was taken.');
        } catch (BadRequestException) {
        }

        $request = $this->request(['error' => 'access_denied', 'state' => 'the-state']);
        $request->getSession()->write(OidcService::SESSION_KEY, $pending);
        $this->assertTrue($this->service()->isGetUserStep($request));
        try {
            $this->service()->getUser($request);
            $this->fail('An error was taken for an identity.');
        } catch (BadRequestException) {
        }

        $request = $this->request(['code' => 'the-code', 'state' => 'the-state']);
        $request->getSession()->write(OidcService::SESSION_KEY, $pending);
        $this->expectException(UnexpectedValueException::class);
        $this->service($this->oidcTokenEndpoint($this->oidcIdToken(['nonce' => 'another-nonce'])))->getUser($request);
    }

    /**
     * A second factor is what the provider calls more than one factor, or something the person has.
     *
     * @return void
     */
    public function testWhatCountsAsASecondFactor(): void
    {
        $this->assertTrue(OidcService::confirmsSecondFactor(['pwd', 'mfa']));
        $this->assertTrue(OidcService::confirmsSecondFactor(['hwk']));
        $this->assertTrue(OidcService::confirmsSecondFactor(['pwd', 'otp']));
        $this->assertFalse(OidcService::confirmsSecondFactor(['pwd']));
        $this->assertFalse(OidcService::confirmsSecondFactor(null));
        $this->assertFalse(OidcService::confirmsSecondFactor('mfa'));
    }

    /**
     * Signing out ends the session with the application at the provider too, and comes back.
     *
     * @return void
     */
    public function testSigningOutEndsTheSessionAtTheProvider(): void
    {
        $this->mockOidcProvider();
        Configure::write('OAuth.providers.oidc.options.issuer', self::oidcIssuer());

        $url = (string)OidcService::endSessionUrl('the-id-token', 'http://localhost/login');

        $this->assertStringStartsWith(self::oidcIssuer() . 'end-session/?', $url);
        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(
            ['id_token_hint' => 'the-id-token', 'post_logout_redirect_uri' => 'http://localhost/login'],
            $query,
        );
        $this->assertNull(OidcService::endSessionUrl('', 'http://localhost/login'));
    }

    /**
     * The service of the test provider.
     *
     * @param \GuzzleHttp\Client|null $http What answers for its token endpoint.
     * @return \Access\Social\Service\OidcService
     */
    private function service(?GuzzleClient $http = null): OidcService
    {
        $service = new OidcService([
            'className' => GenericProvider::class,
            'mapper' => Oidc::class,
            'authParams' => [],
            'options' => [
                'issuer' => self::oidcIssuer(),
                'clientId' => 'test-client',
                'clientSecret' => 'test-secret',
                'redirectUri' => 'http://localhost/auth/oidc',
            ],
            'collaborators' => $http === null ? [] : ['httpClient' => $http],
        ]);
        $service->setProviderName('oidc');

        return $service;
    }

    /**
     * A request with a session of its own.
     *
     * @param array<string, string> $query The query.
     * @return \Cake\Http\ServerRequest
     */
    private function request(array $query = []): ServerRequest
    {
        return new ServerRequest(['url' => '/auth/oidc', 'query' => $query, 'session' => new Session()]);
    }
}
