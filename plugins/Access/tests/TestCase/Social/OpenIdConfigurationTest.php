<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Social;

use Access\Social\OpenIdConfiguration;
use Access\Test\TestCase\OidcProviderTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use UnexpectedValueException;

/**
 * Access\Social\OpenIdConfiguration Test Case
 */
#[UsesClass(OpenIdConfiguration::class)]
class OpenIdConfigurationTest extends TestCase
{
    use OidcProviderTrait;

    /**
     * The addresses come from the discovery document of the issuer.
     *
     * @return void
     */
    public function testTheAddressesComeFromTheIssuer(): void
    {
        $this->mockOidcProvider();

        $configuration = OpenIdConfiguration::of(self::oidcIssuer());

        $this->assertSame(self::oidcIssuer(), $configuration->issuer());
        $this->assertSame('https://idp.example.com/application/o/authorize/', $configuration->authorizationEndpoint());
        $this->assertSame('https://idp.example.com/application/o/token/', $configuration->tokenEndpoint());
        $this->assertSame(self::oidcIssuer() . 'end-session/', $configuration->endSessionEndpoint());
    }

    /**
     * A provider that names another issuer than the one configured is not trusted.
     *
     * @return void
     */
    public function testAnotherIssuerIsRefused(): void
    {
        $this->mockOidcProvider();

        $this->expectException(UnexpectedValueException::class);
        OpenIdConfiguration::of('https://idp.example.com/application/o/someone-else/');
    }

    /**
     * A provider that does not answer is said so, as the sign-in has to end with a message.
     *
     * @return void
     */
    public function testAProviderThatDoesNotAnswerIsSaidSo(): void
    {
        Cache::clear(OpenIdConfiguration::CACHE);
        $this->mockClientGet(
            self::oidcIssuer() . '.well-known/openid-configuration',
            $this->newClientResponse(503),
        );

        $this->expectException(UnexpectedValueException::class);
        OpenIdConfiguration::of(self::oidcIssuer());
    }

    /**
     * The claims of a token that holds are read.
     *
     * @return void
     */
    public function testAnIdTokenThatHoldsIsRead(): void
    {
        $this->mockOidcProvider();

        $claims = OpenIdConfiguration::of(self::oidcIssuer())
            ->idTokenClaims($this->oidcIdToken(), 'test-client', 'the-nonce');

        $this->assertSame('person-at-the-provider', $claims['sub']);
        $this->assertSame(['pwd', 'mfa'], $claims['amr']);
    }

    /**
     * A token for another client, from another issuer, out of its time, for another sign-in or
     * signed by somebody else is refused.
     *
     * @param array<string, mixed> $claims The claims in place of the usual ones.
     * @param string $kid The key id the token names.
     * @return void
     */
    #[DataProvider('tokensThatDoNotHold')]
    public function testAnIdTokenThatDoesNotHoldIsRefused(array $claims, string $kid = 'key-1'): void
    {
        $this->mockOidcProvider();
        $configuration = OpenIdConfiguration::of(self::oidcIssuer());

        $this->expectException(UnexpectedValueException::class);
        $configuration->idTokenClaims($this->oidcIdToken($claims, $kid), 'test-client', 'the-nonce');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1?: string}>
     */
    public static function tokensThatDoNotHold(): array
    {
        return [
            'another client' => [['aud' => 'another-client']],
            'another party among several' => [['aud' => ['test-client', 'another-client'], 'azp' => 'another-client']],
            'another issuer' => [['iss' => 'https://idp.example.com/application/o/someone-else/']],
            'expired' => [['exp' => time() - 600, 'iat' => time() - 900]],
            'another sign-in' => [['nonce' => 'another-nonce']],
            'nobody' => [['sub' => '']],
            'a key the provider does not have' => [[], 'key-2'],
        ];
    }

    /**
     * A token naming a key the provider started using since is read once the keys are read again.
     *
     * @return void
     */
    public function testAKeyNewSinceIsReadAgain(): void
    {
        $this->mockOidcProvider('key-1');
        $configuration = OpenIdConfiguration::of(self::oidcIssuer());
        $configuration->idTokenClaims($this->oidcIdToken(), 'test-client', 'the-nonce');

        // the provider has moved on to a new key since the keys were cached
        $this->cleanupMockResponses();
        $this->mockOidcProvider('key-2', clearCache: false);

        $claims = OpenIdConfiguration::of(self::oidcIssuer())
            ->idTokenClaims($this->oidcIdToken([], 'key-2'), 'test-client', 'the-nonce');
        $this->assertSame('person-at-the-provider', $claims['sub']);
    }
}
