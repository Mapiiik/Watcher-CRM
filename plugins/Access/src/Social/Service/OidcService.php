<?php
declare(strict_types=1);

namespace Access\Social\Service;

use Access\Authentication\AuthLog;
use Access\Social\OpenIdConfiguration;
use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use CakeDC\Auth\Social\Service\OAuth2Service;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use UnexpectedValueException;

/**
 * Signing in through an OpenID Connect provider, such as Authentik or Keycloak.
 *
 * The authorization code flow with PKCE (S256), a state and a nonce, its addresses read from the
 * discovery document of the issuer. The ID token is taken only once its signature, issuer,
 * audience, time and nonce hold. What it says of the methods used to sign in (`amr`, RFC 8176)
 * tells whether the provider asked for a second factor.
 */
class OidcService extends OAuth2Service
{
    /**
     * Where a sign-in waiting for the provider keeps its state, nonce and PKCE verifier.
     */
    public const SESSION_KEY = 'Access.oidc';

    /**
     * The methods of RFC 8176 that are something the person has or the provider's own word for
     * more than one factor. Signing in always takes a first factor besides.
     *
     * @var list<string>
     */
    private const SECOND_FACTORS = ['mfa', 'mca', 'otp', 'hwk', 'swk', 'sc', 'sms', 'tel'];

    /**
     * The configuration of the provider, read when it is first needed.
     */
    private ?OpenIdConfiguration $configuration = null;

    /**
     * Whether the provider has been made from the configuration yet.
     */
    private bool $providerMade = false;

    /**
     * @inheritDoc
     */
    #[Override]
    public function isGetUserStep(ServerRequestInterface $request): bool
    {
        // an error the provider sends back ends the sign-in too, rather than starting another
        return parent::isGetUserStep($request)
            || ($request instanceof ServerRequest && $request->getQuery('error') !== null);
    }

    /**
     * Where the person is sent to sign in, the sign-in being remembered for when they come back.
     *
     * Where the provider cannot be asked how, they are sent straight back with the error OAuth has
     * for it, so that they see why on the sign-in page.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return string
     */
    #[Override]
    public function getAuthorizationUrl(ServerRequestInterface $request): string
    {
        if (!$request instanceof ServerRequest) {
            throw new BadMethodCallException('Request must be an instance of ServerRequest');
        }

        try {
            $provider = $this->provider();
        } catch (UnexpectedValueException $exception) {
            AuthLog::warning('identity provider unavailable', [
                'provider' => $this->getProviderName(),
                'reason' => $exception->getMessage(),
            ]);

            return $this->getConfig('options.redirectUri') . '?error=temporarily_unavailable';
        }

        $nonce = bin2hex(random_bytes(16));
        $url = $provider->getAuthorizationUrl(['nonce' => $nonce] + (array)$this->getConfig('authParams', []));
        $request->getSession()->write(self::SESSION_KEY, [
            'state' => $provider->getState(),
            'nonce' => $nonce,
            'verifier' => $provider->getPkceCode(),
        ]);

        return $url;
    }

    /**
     * The claims of the person who has come back, from the ID token the code is exchanged for.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return array<string, mixed>
     * @throws \Cake\Http\Exception\BadRequestException Where the sign-in did not finish or is not this one.
     * @throws \UnexpectedValueException Where the provider's answer does not hold.
     */
    #[Override]
    public function getUser(ServerRequestInterface $request): array
    {
        if (!$request instanceof ServerRequest) {
            throw new BadMethodCallException('Request must be an instance of ServerRequest');
        }

        // what was remembered is good for one answer only
        $pending = (array)$request->getSession()->consume(self::SESSION_KEY);
        $error = $request->getQuery('error');
        if ($error !== null) {
            throw new BadRequestException(sprintf('The provider ended the sign-in: %s', (string)$error));
        }
        $state = (string)$request->getQuery('state');
        if ($state === '' || !hash_equals((string)($pending['state'] ?? ''), $state)) {
            throw new BadRequestException('Invalid OAuth2 state');
        }

        $provider = $this->provider();
        $provider->setPkceCode((string)($pending['verifier'] ?? ''));
        try {
            $token = $provider->getAccessToken('authorization_code', ['code' => (string)$request->getQuery('code')]);
        } catch (Throwable $exception) {
            throw new UnexpectedValueException(
                'The code could not be exchanged: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
        if (!$token instanceof AccessToken) {
            throw new UnexpectedValueException('The provider answered with no access token.');
        }

        $idToken = (string)($token->getValues()['id_token'] ?? '');
        if ($idToken === '') {
            throw new UnexpectedValueException('The provider answered with no ID token.');
        }
        $claims = $this->configuration()->idTokenClaims(
            $idToken,
            (string)$this->getConfig('options.clientId'),
            (string)($pending['nonce'] ?? ''),
        );

        return [
            'token' => $token,
            'id_token' => $idToken,
            'second_factor_at_provider' => self::confirmsSecondFactor($claims['amr'] ?? null),
        ] + $claims;
    }

    /**
     * Whether the methods an ID token says the person signed in with include a second factor.
     *
     * @param mixed $amr The `amr` claim.
     * @return bool
     */
    public static function confirmsSecondFactor(mixed $amr): bool
    {
        return is_array($amr) && array_intersect($amr, self::SECOND_FACTORS) !== [];
    }

    /**
     * Where somebody who signed in through the provider is sent to end their session with the
     * application there too, coming back to an address of the application. Null where the provider
     * offers no such address or cannot be asked.
     *
     * @param string $idToken The ID token they signed in with.
     * @param string $backTo The address of the application to come back to.
     * @param string $name The name of the provider in the configuration.
     * @return string|null
     */
    public static function endSessionUrl(string $idToken, string $backTo, string $name = 'oidc'): ?string
    {
        $issuer = (string)Configure::read('OAuth.providers.' . $name . '.options.issuer');
        if ($issuer === '' || $idToken === '') {
            return null;
        }

        try {
            $endpoint = OpenIdConfiguration::of($issuer)->endSessionEndpoint();
        } catch (UnexpectedValueException $exception) {
            AuthLog::warning('identity provider unavailable', [
                'provider' => $name,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
        if ($endpoint === null) {
            return null;
        }

        return $endpoint . (str_contains($endpoint, '?') ? '&' : '?') . http_build_query([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $backTo,
        ]);
    }

    /**
     * The provider is made when it is first needed, as its addresses are read from the issuer.
     *
     * @param array<string, mixed> $config The configuration of the provider.
     * @return void
     */
    #[Override]
    protected function setProvider(array $config): void
    {
        $this->providerMade = false;
    }

    /**
     * The OAuth 2 provider, with the addresses the issuer names.
     *
     * @return \League\OAuth2\Client\Provider\AbstractProvider
     */
    private function provider(): AbstractProvider
    {
        if ($this->providerMade) {
            return $this->provider;
        }

        $configuration = $this->configuration();
        $class = $this->getConfig('className') ?: GenericProvider::class;
        $options = [
            'urlAuthorize' => $configuration->authorizationEndpoint(),
            'urlAccessToken' => $configuration->tokenEndpoint(),
            'urlResourceOwnerDetails' => $configuration->userinfoEndpoint(),
            'scopes' => ['openid', 'profile', 'email'],
            'scopeSeparator' => ' ',
            'pkceMethod' => AbstractProvider::PKCE_METHOD_S256,
        ] + (array)$this->getConfig('options');
        unset($options['issuer']);

        $provider = new $class($options, (array)$this->getConfig('collaborators', []));
        if (!$provider instanceof AbstractProvider) {
            throw new UnexpectedValueException(sprintf('%s is no OAuth 2 provider.', $class));
        }
        $this->provider = $provider;
        $this->providerMade = true;

        return $this->provider;
    }

    /**
     * The configuration the issuer publishes.
     *
     * @return \Access\Social\OpenIdConfiguration
     */
    private function configuration(): OpenIdConfiguration
    {
        return $this->configuration ??= OpenIdConfiguration::of((string)$this->getConfig('options.issuer'));
    }
}
