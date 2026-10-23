<?php
declare(strict_types=1);

namespace Access\Test\TestCase;

use Access\Social\OpenIdConfiguration;
use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;
use Firebase\JWT\JWT;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use OpenSSLAsymmetricKey;

/**
 * An OpenID Connect provider for tests: its discovery document and signing keys answered by the
 * mocked HTTP client, ID tokens signed with a key made for the test run, and its token endpoint
 * answered by a mocked Guzzle client.
 */
trait OidcProviderTrait
{
    use HttpClientTrait;

    /**
     * The key the ID tokens are signed with, made once for the test run.
     */
    private static ?OpenSSLAsymmetricKey $oidcKey = null;

    /**
     * The issuer of the test provider.
     *
     * @return string
     */
    protected static function oidcIssuer(): string
    {
        return 'https://idp.example.com/application/o/watcher/';
    }

    /**
     * Answers the discovery document and the signing keys of the test provider.
     *
     * @param string $kid The key id the keys are published under.
     * @param bool $clearCache Whether to forget what was read from the provider before.
     * @return void
     */
    protected function mockOidcProvider(string $kid = 'key-1', bool $clearCache = true): void
    {
        if ($clearCache) {
            Cache::clear(OpenIdConfiguration::CACHE);
        }
        $issuer = self::oidcIssuer();
        $this->mockClientGet(
            $issuer . '.well-known/openid-configuration',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'issuer' => $issuer,
                'authorization_endpoint' => 'https://idp.example.com/application/o/authorize/',
                'token_endpoint' => 'https://idp.example.com/application/o/token/',
                'userinfo_endpoint' => 'https://idp.example.com/application/o/userinfo/',
                'jwks_uri' => $issuer . 'jwks/',
                'end_session_endpoint' => $issuer . 'end-session/',
            ])),
        );
        $this->mockClientGet(
            $issuer . 'jwks/',
            $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode([
                'keys' => [$this->oidcPublicKey($kid)],
            ])),
        );
    }

    /**
     * An ID token of the test provider.
     *
     * @param array<string, mixed> $claims Claims in place of the usual ones.
     * @param string $kid The key id to name.
     * @return string
     */
    protected function oidcIdToken(array $claims = [], string $kid = 'key-1'): string
    {
        openssl_pkey_export(self::oidcKey(), $pem);

        return JWT::encode($claims + [
            'iss' => self::oidcIssuer(),
            'aud' => 'test-client',
            'sub' => 'person-at-the-provider',
            'iat' => time(),
            'exp' => time() + 300,
            'nonce' => 'the-nonce',
            'amr' => ['pwd', 'mfa'],
            'preferred_username' => 'jnovak',
            'email' => 'jan.novak@example.com',
        ], (string)$pem, 'RS256', $kid);
    }

    /**
     * A Guzzle client whose token endpoint answers with an ID token.
     *
     * @param string $idToken The ID token.
     * @return \GuzzleHttp\Client
     */
    protected function oidcTokenEndpoint(string $idToken): GuzzleClient
    {
        return new GuzzleClient(['handler' => HandlerStack::create(new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], (string)json_encode([
                'access_token' => 'access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'id_token' => $idToken,
            ])),
        ]))]);
    }

    /**
     * The public half of the key, as a JWK.
     *
     * @param string $kid The key id.
     * @return array<string, string>
     */
    private function oidcPublicKey(string $kid): array
    {
        $details = (array)openssl_pkey_get_details(self::oidcKey());
        $encode = fn(string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return [
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'kid' => $kid,
            'n' => $encode((string)$details['rsa']['n']),
            'e' => $encode((string)$details['rsa']['e']),
        ];
    }

    /**
     * @return \OpenSSLAsymmetricKey
     */
    private static function oidcKey(): OpenSSLAsymmetricKey
    {
        if (self::$oidcKey === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            assert($key instanceof OpenSSLAsymmetricKey);
            self::$oidcKey = $key;
        }

        return self::$oidcKey;
    }
}
