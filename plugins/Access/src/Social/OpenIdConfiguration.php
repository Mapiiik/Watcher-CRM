<?php
declare(strict_types=1);

namespace Access\Social;

use Cake\Cache\Cache;
use Cake\Http\Client;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use stdClass;
use Throwable;
use UnexpectedValueException;

/**
 * What an OpenID Connect provider says of itself at its discovery address, and the checks of the ID
 * tokens it signs.
 *
 * The document and the signing keys are cached; the keys are read again once when a token names a
 * key not among them, as after the provider changed its key.
 */
final class OpenIdConfiguration
{
    /**
     * Where the document and the keys are cached.
     */
    public const CACHE = 'default';

    /**
     * How long a request to the provider may take, in seconds.
     */
    private const TIMEOUT = 10;

    /**
     * How far the clocks of the provider and of the application may differ, in seconds.
     */
    private const LEEWAY = 60;

    /**
     * @param string $issuer The issuer, as the provider names itself.
     * @param array<string, mixed> $document The discovery document.
     * @param \Cake\Http\Client|null $http The client the keys are read with.
     */
    private function __construct(
        private string $issuer,
        private array $document,
        private ?Client $http = null,
    ) {
    }

    /**
     * The configuration of the provider an issuer names.
     *
     * @param string $issuer The issuer, as configured.
     * @param \Cake\Http\Client|null $http The client the provider is asked with.
     * @return self
     * @throws \UnexpectedValueException Where the provider does not answer as it should.
     */
    public static function of(string $issuer, ?Client $http = null): self
    {
        $url = rtrim($issuer, '/') . '/.well-known/openid-configuration';
        $document = Cache::remember(
            self::cacheKey('configuration', $issuer),
            fn(): array => self::fetch($url, $http),
            self::CACHE,
        );
        // the issuer is what the tokens are checked against, so it has to be the one configured
        if (!is_array($document) || rtrim((string)($document['issuer'] ?? ''), '/') !== rtrim($issuer, '/')) {
            Cache::delete(self::cacheKey('configuration', $issuer), self::CACHE);
            throw new UnexpectedValueException('The provider names another issuer than the one configured.');
        }

        return new self((string)$document['issuer'], $document, $http);
    }

    /**
     * The issuer, as the provider names itself.
     *
     * @return string
     */
    public function issuer(): string
    {
        return $this->issuer;
    }

    /**
     * Where people are sent to sign in.
     *
     * @return string
     */
    public function authorizationEndpoint(): string
    {
        return $this->endpoint('authorization_endpoint');
    }

    /**
     * Where the code is exchanged for the tokens.
     *
     * @return string
     */
    public function tokenEndpoint(): string
    {
        return $this->endpoint('token_endpoint');
    }

    /**
     * Where the claims of the person are read with the access token.
     *
     * @return string
     */
    public function userinfoEndpoint(): string
    {
        return $this->endpoint('userinfo_endpoint');
    }

    /**
     * Where somebody is sent to end their session with the application at the provider, if it says.
     *
     * @return string|null
     */
    public function endSessionEndpoint(): ?string
    {
        $endpoint = $this->document['end_session_endpoint'] ?? null;

        return is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }

    /**
     * The claims of an ID token, once its signature, issuer, audience, time and nonce are checked.
     *
     * @param string $idToken The ID token.
     * @param string $clientId The client the token has to be for.
     * @param string $nonce The nonce the sign-in was started with.
     * @return array<string, mixed>
     * @throws \UnexpectedValueException Where the token does not hold.
     */
    public function idTokenClaims(string $idToken, string $clientId, string $nonce): array
    {
        $claims = $this->decode($idToken, false);

        if (($claims->iss ?? null) !== $this->issuer) {
            throw new UnexpectedValueException('The ID token is from another issuer.');
        }
        $audience = (array)($claims->aud ?? []);
        if (!in_array($clientId, $audience, true)) {
            throw new UnexpectedValueException('The ID token is for another client.');
        }
        if (count($audience) > 1 && ($claims->azp ?? null) !== $clientId) {
            throw new UnexpectedValueException('The ID token is for another party.');
        }
        if ($nonce === '' || !is_string($claims->nonce ?? null) || !hash_equals($nonce, $claims->nonce)) {
            throw new UnexpectedValueException('The ID token does not answer this sign-in.');
        }
        if (!is_string($claims->sub ?? null) || $claims->sub === '') {
            throw new UnexpectedValueException('The ID token names nobody.');
        }

        return (array)json_decode((string)json_encode($claims), true);
    }

    /**
     * The claims of a token whose signature holds, reading the keys again once where it names one
     * not among them.
     *
     * @param string $token The token.
     * @param bool $refreshed Whether the keys have just been read again.
     * @return \stdClass
     */
    private function decode(string $token, bool $refreshed): stdClass
    {
        $keys = $this->keys($refreshed);
        $was = JWT::$leeway;
        JWT::$leeway = self::LEEWAY;
        try {
            return JWT::decode($token, $keys);
        } catch (UnexpectedValueException $exception) {
            $unknownKey = str_contains($exception->getMessage(), '"kid"');
            if ($unknownKey && !$refreshed) {
                return $this->decode($token, true);
            }

            throw $exception;
        } finally {
            JWT::$leeway = $was;
        }
    }

    /**
     * The signing keys of the provider.
     *
     * @param bool $refresh Whether to read them again rather than from the cache.
     * @return array<string, \Firebase\JWT\Key>
     */
    private function keys(bool $refresh): array
    {
        $key = self::cacheKey('keys', $this->issuer);
        if ($refresh) {
            Cache::delete($key, self::CACHE);
        }
        $set = Cache::remember($key, fn(): array => self::fetch($this->endpoint('jwks_uri'), $this->http), self::CACHE);

        try {
            return JWK::parseKeySet((array)$set, 'RS256');
        } catch (Throwable $exception) {
            throw new UnexpectedValueException('The signing keys of the provider cannot be read.', 0, $exception);
        }
    }

    /**
     * An address the document names.
     *
     * @param string $name The name in the document.
     * @return string
     */
    private function endpoint(string $name): string
    {
        $endpoint = $this->document[$name] ?? null;
        if (!is_string($endpoint) || !str_starts_with($endpoint, 'https://')) {
            throw new UnexpectedValueException(sprintf('The provider names no %s.', $name));
        }

        return $endpoint;
    }

    /**
     * A JSON document from the provider.
     *
     * @param string $url Where.
     * @param \Cake\Http\Client|null $http The client.
     * @return array<string, mixed>
     */
    private static function fetch(string $url, ?Client $http): array
    {
        try {
            $response = ($http ?? new Client(['timeout' => self::TIMEOUT]))->get($url);
        } catch (Throwable $exception) {
            throw new UnexpectedValueException(
                'The provider cannot be reached: ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
        $json = $response->isOk() ? $response->getJson() : null;
        if (!is_array($json)) {
            throw new UnexpectedValueException(
                sprintf('The provider answered %d at %s.', $response->getStatusCode(), $url),
            );
        }

        return $json;
    }

    /**
     * @param string $what What is cached.
     * @param string $issuer Of which provider.
     * @return string
     */
    private static function cacheKey(string $what, string $issuer): string
    {
        return 'access_oidc_' . $what . '_' . hash('sha256', rtrim($issuer, '/'));
    }
}
