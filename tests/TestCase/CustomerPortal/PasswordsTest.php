<?php
declare(strict_types=1);

namespace App\Test\TestCase\CustomerPortal;

use App\CustomerPortal\Passwords;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClassConstant;

/**
 * App\CustomerPortal\Passwords Test Case
 *
 * Every shape a customer's password may still be stored in has to let the right password in and
 * keep a wrong one out, and has to be recognised as due for a fresh hash.
 */
#[CoversClass(Passwords::class)]
class PasswordsTest extends TestCase
{
    /**
     * A password is stored as Argon2id with the cost NÚKIB asks for.
     *
     * @return void
     */
    public function testAPasswordIsHashedWithArgon2id(): void
    {
        $info = password_get_info(Passwords::hash('correct horse'));

        $this->assertSame('argon2id', $info['algoName']);
        $this->assertSame(Passwords::OPTIONS, $info['options']);
    }

    /**
     * A fresh hash lets the password in, and nothing else.
     *
     * @return void
     */
    public function testAFreshHashIsVerified(): void
    {
        $stored = Passwords::hash('correct horse');

        $this->assertTrue(Passwords::verify('correct horse', $stored));
        $this->assertFalse(Passwords::verify('Correct horse', $stored));
        $this->assertFalse(Passwords::needsRehash($stored));
    }

    /**
     * A bare SHA-1 from before still signs in, in either letter case, and is due for a fresh hash.
     *
     * @return void
     */
    public function testABareSha1IsVerified(): void
    {
        $stored = sha1('correct horse');

        $this->assertTrue(Passwords::isLegacy($stored));
        $this->assertTrue(Passwords::verify('correct horse', $stored));
        $this->assertTrue(Passwords::verify('correct horse', strtoupper($stored)));
        $this->assertFalse(Passwords::verify('wrong horse', $stored));
        $this->assertTrue(Passwords::needsRehash($stored));
    }

    /**
     * A wrapped SHA-1 signs in with the password, never with the SHA-1 it wraps, which is what a
     * leaked copy of the old table would hold.
     *
     * @return void
     */
    public function testAWrappedSha1IsVerified(): void
    {
        $sha1 = sha1('correct horse');
        $stored = Passwords::wrap(strtoupper($sha1));

        $this->assertTrue(Passwords::isWrapped($stored));
        $this->assertFalse(Passwords::isLegacy($stored));
        $this->assertTrue(Passwords::verify('correct horse', $stored));
        $this->assertFalse(Passwords::verify($sha1, $stored));
        $this->assertFalse(Passwords::verify('wrong horse', $stored));
        $this->assertTrue(Passwords::needsRehash($stored));
    }

    /**
     * A hash with a lower cost, or of another algorithm, is due for a fresh one.
     *
     * @return void
     */
    public function testAWeakerHashIsDueForAFreshOne(): void
    {
        $weaker = password_hash('correct horse', PASSWORD_ARGON2ID, ['memory_cost' => 1024] + Passwords::OPTIONS);
        $bcrypt = password_hash('correct horse', PASSWORD_BCRYPT);

        $this->assertTrue(Passwords::verify('correct horse', $weaker));
        $this->assertTrue(Passwords::needsRehash($weaker));
        $this->assertTrue(Passwords::verify('correct horse', $bcrypt));
        $this->assertTrue(Passwords::needsRehash($bcrypt));
    }

    /**
     * A login that does not exist costs one Argon2id verification at today's cost.
     *
     * @return void
     */
    public function testAnUnknownLoginCostsAsMuchAsAKnownOne(): void
    {
        $decoy = (new ReflectionClassConstant(Passwords::class, 'DECOY'))->getValue();

        $this->assertIsString($decoy);
        $this->assertStringStartsWith('$argon2id$', $decoy);
        $this->assertFalse(Passwords::needsRehash($decoy), 'The decoy has to be hashed again with Passwords::OPTIONS.');
    }
}
