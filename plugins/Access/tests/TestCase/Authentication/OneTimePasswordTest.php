<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\OneTimePassword;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Authentication\OneTimePassword Test Case
 */
#[UsesClass(OneTimePassword::class)]
class OneTimePasswordTest extends TestCase
{
    /**
     * The secret of the test vectors of RFC 6238 for SHA-1, "12345678901234567890", in Base32.
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    /**
     * The codes are those of RFC 6238, appendix B, cut to six digits as the apps show them.
     *
     * @param int $time The time.
     * @param string $code The code.
     * @return void
     */
    #[DataProvider('rfcProvider')]
    public function testTheCodesAreThoseOfTheRfc(int $time, string $code): void
    {
        $this->assertSame($code, OneTimePassword::code(self::RFC_SECRET, intdiv($time, 30)));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function rfcProvider(): array
    {
        return [
            'at 59' => [59, '287082'],
            'at 1111111109' => [1111111109, '081804'],
            'at 1111111111' => [1111111111, '050471'],
            'at 1234567890' => [1234567890, '005924'],
            'at 2000000000' => [2000000000, '279037'],
            'at 20000000000' => [20000000000, '353130'],
        ];
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
     * A new secret is 160 bits in Base32, which fits where secrets are kept, and makes codes.
     *
     * @return void
     */
    public function testANewSecretFitsAndWorks(): void
    {
        $secret = OneTimePassword::createSecret();

        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertNotSame($secret, OneTimePassword::createSecret());
        $code = OneTimePassword::code($secret, intdiv(time(), 30));
        $this->assertSame(intdiv(time(), 30), OneTimePassword::verify($secret, $code));
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
    }
}
