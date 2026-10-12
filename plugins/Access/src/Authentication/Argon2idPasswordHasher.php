<?php
declare(strict_types=1);

namespace Access\Authentication;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Override;

/**
 * Passwords hashed with Argon2id, at least as strong as NÚKIB asks for.
 *
 * Hashes of another kind still verify, and say they want hashing again, so a password is moved to
 * Argon2id the next time its owner signs in with it.
 */
class Argon2idPasswordHasher extends DefaultPasswordHasher
{
    /**
     * Argon2id cost, NÚKIB "Minimální požadavky na kryptografické algoritmy" 4.1.
     *
     * @var array{memory_cost: int, time_cost: int, threads: int}
     */
    public const OPTIONS = ['memory_cost' => 65536, 'time_cost' => 3, 'threads' => 4];

    /**
     * An Argon2id hash of a random password nobody knows.
     */
    private const DECOY = '$argon2id$v=19$m=65536,t=3,p=4$MWw2d0RTelJpUjFhRmRGMA$'
        . '5biMSaVveBWBz801JpWwmyQTU6k4FKrJ5kR/LwrW2C4';

    /**
     * Default config for this object.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'hashType' => PASSWORD_ARGON2ID,
        'hashOptions' => self::OPTIONS,
    ];

    /**
     * Checks a password against a stored hash.
     *
     * Where nothing that could match is stored - an account that does not exist, or one that
     * cannot sign in with a password - a decoy is verified all the same, so that how long the
     * answer takes does not tell which accounts exist.
     *
     * @param string $password The password as typed.
     * @param string $hashedPassword What is stored.
     * @return bool
     */
    #[Override]
    public function check(string $password, string $hashedPassword): bool
    {
        if (password_get_info($hashedPassword)['algo'] === null) {
            password_verify($password, self::DECOY);

            return false;
        }

        return parent::check($password, $hashedPassword);
    }
}
