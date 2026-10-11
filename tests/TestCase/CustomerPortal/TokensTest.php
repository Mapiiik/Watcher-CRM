<?php
declare(strict_types=1);

namespace App\Test\TestCase\CustomerPortal;

use App\CustomerPortal\Tokens;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

/**
 * App\CustomerPortal\Tokens Test Case
 *
 * A token is all that stands between the portal and every other customer's records, so anything
 * but one this application signed, still valid, has to read as no token at all.
 */
#[CoversClass(Tokens::class)]
class TokensTest extends TestCase
{
    private const LOGIN_ID = '5457ac8a-1c92-4d5f-a035-41de7fad3509';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * An issued token reads back as the login and customer it was issued for.
     *
     * @return void
     */
    public function testAnIssuedTokenReadsBack(): void
    {
        $issued = Tokens::issue(self::LOGIN_ID, self::CUSTOMER_ID, 8);

        $this->assertEqualsWithDelta(time() + 8 * 3600, $issued['expires'], 5);
        $this->assertSame(
            ['login_id' => self::LOGIN_ID, 'customer_id' => self::CUSTOMER_ID, 'expires' => $issued['expires']],
            Tokens::read($issued['token']),
        );
    }

    /**
     * A token naming another customer than it was signed for is not one.
     *
     * @return void
     */
    public function testAChangedTokenIsRejected(): void
    {
        [$payload, $signature] = explode('.', Tokens::issue(self::LOGIN_ID, self::CUSTOMER_ID, 8)['token']);
        $forged = rtrim(strtr(base64_encode((string)json_encode([
            'l' => self::LOGIN_ID,
            'c' => 'ae128a49-82fd-4b80-921f-f11af75fd113',
            'e' => time() + 3600,
        ])), '+/', '-_'), '=');

        $this->assertNull(Tokens::read($forged . '.' . $signature));
        $this->assertNull(Tokens::read($payload . '.' . strrev($signature)));
        $this->assertNull(Tokens::read($payload));
        $this->assertNull(Tokens::read(''));
        $this->assertNull(Tokens::read('not.a.token'));
    }

    /**
     * A token signed under another salt, by another installation, is not one.
     *
     * @return void
     */
    public function testATokenOfAnotherInstallationIsRejected(): void
    {
        $salt = Security::getSalt();
        $token = Tokens::issue(self::LOGIN_ID, self::CUSTOMER_ID, 8)['token'];

        Security::setSalt($salt . ' of another installation');
        try {
            $this->assertNull(Tokens::read($token));
        } finally {
            Security::setSalt($salt);
        }
    }

    /**
     * A token past its time is not one, signed or not.
     *
     * @return void
     */
    public function testAnExpiredTokenIsRejected(): void
    {
        $encode = new ReflectionMethod(Tokens::class, 'encode');
        $sign = new ReflectionMethod(Tokens::class, 'sign');
        $payload = $encode->invoke(null, (string)json_encode([
            'l' => self::LOGIN_ID,
            'c' => self::CUSTOMER_ID,
            'e' => time() - 1,
        ]));

        $this->assertNull(Tokens::read($payload . '.' . $encode->invoke(null, $sign->invoke(null, $payload))));
    }
}
