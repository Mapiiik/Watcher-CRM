<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\OneTimePassword;
use Access\Authentication\OneTimePasswordAlgorithm;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Authentication\OneTimePassword Test Case
 */
#[UsesClass(OneTimePassword::class)]
#[UsesClass(OneTimePasswordAlgorithm::class)]
class OneTimePasswordTest extends TestCase
{
    /**
     * The secret of the test vectors of RFC 6238 for SHA-1, "12345678901234567890", in Base32.
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    /**
     * The secret of the test vectors of RFC 6238 for SHA-256, "12345678901234567890123456789012",
     * in Base32. The text of the RFC names the 20-byte one for all three; its code, and erratum
     * 2866, use one as long as the hash.
     */
    private const RFC_SECRET_SHA256 = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZA';

    /**
     * The secret of the test vectors of RFC 6238 for SHA-512, "1234567890" six times and "1234",
     * in Base32.
     */
    private const RFC_SECRET_SHA512 = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'
        . 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQGEZDGNA';

    /**
     * The codes are those of RFC 6238, appendix B, cut to six digits as the apps show them.
     *
     * @param \Access\Authentication\OneTimePasswordAlgorithm $algorithm The hash function.
     * @param string $secret The secret in Base32.
     * @param int $time The time.
     * @param string $code The code.
     * @return void
     */
    #[DataProvider('rfcProvider')]
    public function testTheCodesAreThoseOfTheRfc(
        OneTimePasswordAlgorithm $algorithm,
        string $secret,
        int $time,
        string $code,
    ): void {
        $this->assertSame($code, OneTimePassword::code($secret, intdiv($time, 30), $algorithm));
        $this->assertSame(intdiv($time, 30), OneTimePassword::verify($secret, $code, $time, $algorithm));
    }

    /**
     * @return array<string, array{\Access\Authentication\OneTimePasswordAlgorithm, string, int, string}>
     */
    public static function rfcProvider(): array
    {
        $vectors = [];
        foreach (
            [
                [59, '287082', '119246', '693936'],
                [1111111109, '081804', '084774', '091201'],
                [1111111111, '050471', '062674', '943326'],
                [1234567890, '005924', '819424', '441116'],
                [2000000000, '279037', '698825', '618901'],
                [20000000000, '353130', '737706', '863826'],
            ] as [$time, $sha1, $sha256, $sha512]
        ) {
            $vectors['SHA-1 at ' . $time] = [OneTimePasswordAlgorithm::Sha1, self::RFC_SECRET, $time, $sha1];
            $vectors['SHA-256 at ' . $time] = [
                OneTimePasswordAlgorithm::Sha256,
                self::RFC_SECRET_SHA256,
                $time,
                $sha256,
            ];
            $vectors['SHA-512 at ' . $time] = [
                OneTimePasswordAlgorithm::Sha512,
                self::RFC_SECRET_SHA512,
                $time,
                $sha512,
            ];
        }

        return $vectors;
    }

    /**
     * A code made with another hash function is not right.
     *
     * @return void
     */
    public function testACodeOfAnotherHashFunctionIsNotRight(): void
    {
        $code = OneTimePassword::code(self::RFC_SECRET_SHA256, 1000, OneTimePasswordAlgorithm::Sha1);

        $this->assertNull(
            OneTimePassword::verify(self::RFC_SECRET_SHA256, $code, 1000 * 30, OneTimePasswordAlgorithm::Sha256),
        );
    }

    /**
     * A code is right for its own time step and the one on either side, and says which it was.
     *
     * @return void
     */
    public function testACodeIsRightForItsStepAndTheOnesBesideIt(): void
    {
        $code = OneTimePassword::code(self::RFC_SECRET, 1000);

        $this->assertSame(1000, OneTimePassword::verify(self::RFC_SECRET, $code, 1000 * 30));
        $this->assertSame(1000, OneTimePassword::verify(self::RFC_SECRET, $code, 1001 * 30));
        $this->assertSame(1000, OneTimePassword::verify(self::RFC_SECRET, $code, 999 * 30));
        $this->assertNull(OneTimePassword::verify(self::RFC_SECRET, $code, 1002 * 30));
        $this->assertNull(OneTimePassword::verify(self::RFC_SECRET, '12345', 1000 * 30));
    }

    /**
     * A new secret is as long as the hash, in Base32, and makes codes.
     *
     * @return void
     */
    public function testANewSecretIsAsLongAsTheHashAndWorks(): void
    {
        foreach (['SHA1' => 32, 'SHA256' => 52, 'SHA512' => 103] as $name => $length) {
            $algorithm = OneTimePasswordAlgorithm::from($name);
            $secret = OneTimePassword::createSecret($algorithm);

            $this->assertMatchesRegularExpression('/^[A-Z2-7]{' . $length . '}$/', $secret);
            $this->assertNotSame($secret, OneTimePassword::createSecret($algorithm));
            $code = OneTimePassword::code($secret, intdiv(time(), 30), $algorithm);
            $this->assertSame(intdiv(time(), 30), OneTimePassword::verify($secret, $code, algorithm: $algorithm));
        }
    }

    /**
     * A stored name that means nothing stands for SHA-1, which every app understands.
     *
     * @return void
     */
    public function testAnUnknownNameIsSha1(): void
    {
        $this->assertSame(OneTimePasswordAlgorithm::Sha256, OneTimePasswordAlgorithm::of('SHA256'));
        $this->assertSame(OneTimePasswordAlgorithm::Sha1, OneTimePasswordAlgorithm::of('MD5'));
        $this->assertSame(OneTimePasswordAlgorithm::Sha1, OneTimePasswordAlgorithm::of(null));
        $this->assertSame('SHA-512', OneTimePasswordAlgorithm::Sha512->title());
        $this->assertSame(['SHA1', 'SHA256', 'SHA512'], array_keys(OneTimePasswordAlgorithm::options()));
    }

    /**
     * The address the app reads names the application and the account, and the QR code carries it.
     *
     * @return void
     */
    public function testTheAddressAndItsQrCode(): void
    {
        $uri = OneTimePassword::uri('crm.example.com', 'jnovak', self::RFC_SECRET);

        $this->assertSame(
            'otpauth://totp/crm.example.com:jnovak?secret=' . self::RFC_SECRET
            . '&issuer=crm.example.com&algorithm=SHA1&digits=6&period=30',
            $uri,
        );
        $this->assertStringStartsWith('data:image/svg+xml;base64,', OneTimePassword::qrCode($uri));
        $this->assertStringContainsString(
            '&algorithm=SHA256&',
            OneTimePassword::uri('crm.example.com', 'jnovak', self::RFC_SECRET_SHA256, OneTimePasswordAlgorithm::Sha256),
        );
    }
}
