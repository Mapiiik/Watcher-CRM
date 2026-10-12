<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\Argon2idPasswordHasher;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Authentication\Argon2idPasswordHasher Test Case
 */
#[UsesClass(Argon2idPasswordHasher::class)]
class Argon2idPasswordHasherTest extends TestCase
{
    /**
     * A password is hashed with the cost NÚKIB asks for. A PHP built with libsodium alone has
     * Argon2id but refuses more than one thread, so this is also what tells such a build apart.
     *
     * @return void
     */
    public function testAPasswordIsHashedWithArgon2idAtTheCostAskedFor(): void
    {
        $hasher = new Argon2idPasswordHasher();
        $hash = $hasher->hash('correct horse battery staple');

        $this->assertStringStartsWith('$argon2id$v=19$m=65536,t=3,p=4$', $hash);
        $this->assertTrue($hasher->check('correct horse battery staple', $hash));
        $this->assertFalse($hasher->check('correct horse battery stapler', $hash));
        $this->assertFalse($hasher->needsRehash($hash));
    }

    /**
     * A bcrypt hash from before still signs its owner in, and asks to be replaced when it does.
     *
     * @return void
     */
    public function testAnOlderHashStillVerifiesAndAsksToBeReplaced(): void
    {
        $hasher = new Argon2idPasswordHasher();
        $bcrypt = password_hash('correct horse battery staple', PASSWORD_BCRYPT);

        $this->assertTrue($hasher->check('correct horse battery staple', $bcrypt));
        $this->assertTrue($hasher->needsRehash($bcrypt));
    }

    /**
     * Where nothing that could match is stored, nothing matches - not even what is stored.
     *
     * @return void
     */
    public function testNothingMatchesWhereNoHashIsStored(): void
    {
        $hasher = new Argon2idPasswordHasher();

        $this->assertFalse($hasher->check('', ''));
        $this->assertFalse($hasher->check('!', '!'));
        $this->assertFalse($hasher->check('anything', '*'));
    }
}
