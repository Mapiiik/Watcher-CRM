<?php
declare(strict_types=1);

namespace App\CustomerPortal;

/**
 * Customer portal passwords: stored with Argon2id, at least as strong as NÚKIB asks for.
 *
 * Passwords from before Argon2id still sign in. They were plain SHA-1, some are kept wrapped in
 * Argon2id until the customer signs in, and either is replaced with plain Argon2id when they do.
 */
final class Passwords
{
    /**
     * Argon2id cost, NÚKIB "Minimální požadavky na kryptografické algoritmy" 4.1.
     *
     * @var array{memory_cost: int, time_cost: int, threads: int}
     */
    public const OPTIONS = ['memory_cost' => 65536, 'time_cost' => 3, 'threads' => 4];

    /**
     * Marks an old SHA-1 hash wrapped in Argon2id, `$sha1` followed by the Argon2id hash.
     */
    public const WRAPPED_PREFIX = '$sha1';

    /**
     * An Argon2id hash of a random password nobody knows, verified for a login that does not exist.
     */
    private const DECOY = '$argon2id$v=19$m=65536,t=3,p=4$V1p0MVAxOWNiWVZvY1VSQg$'
        . '9PYmypZ+gWqWF07UnBuEaNDpFrcDOqbV5ZhtvYbNgs4';

    /**
     * Hashes a password the way it is stored.
     *
     * @param string $password The password as the customer types it.
     * @return string
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, self::OPTIONS);
    }

    /**
     * Wraps an old SHA-1 hash in Argon2id, so that it stops being one that can be cracked offline.
     *
     * @param string $sha1 The stored SHA-1, 40 hex digits.
     * @return string
     */
    public static function wrap(string $sha1): string
    {
        return self::WRAPPED_PREFIX . password_hash(strtolower($sha1), PASSWORD_ARGON2ID, self::OPTIONS);
    }

    /**
     * Whether a password matches what is stored, in any of the shapes it may be stored in.
     *
     * @param string $password The password as the customer typed it.
     * @param string $stored What is stored.
     * @return bool
     */
    public static function verify(string $password, string $stored): bool
    {
        if (self::isLegacy($stored)) {
            return hash_equals(strtolower($stored), sha1($password));
        }
        if (self::isWrapped($stored)) {
            return password_verify(sha1($password), substr($stored, strlen(self::WRAPPED_PREFIX)));
        }

        return password_verify($password, $stored);
    }

    /**
     * Verifies a password of a login that does not exist, as long as a real one takes, so that the
     * time of the answer does not tell which logins exist. Never matches.
     *
     * @param string $password The password as the customer typed it.
     * @return false
     */
    public static function verifyUnknown(string $password): false
    {
        password_verify($password, self::DECOY);

        return false;
    }

    /**
     * Whether what is stored should be replaced with a fresh hash once the password is known.
     *
     * @param string $stored What is stored.
     * @return bool
     */
    public static function needsRehash(string $stored): bool
    {
        if (self::isLegacy($stored) || self::isWrapped($stored)) {
            return true;
        }

        return password_needs_rehash($stored, PASSWORD_ARGON2ID, self::OPTIONS);
    }

    /**
     * Whether what is stored is a bare SHA-1.
     *
     * @param string $stored What is stored.
     * @return bool
     */
    public static function isLegacy(string $stored): bool
    {
        return preg_match('/^[0-9a-f]{40}$/i', $stored) === 1;
    }

    /**
     * Whether what is stored is a SHA-1 wrapped in Argon2id.
     *
     * @param string $stored What is stored.
     * @return bool
     */
    public static function isWrapped(string $stored): bool
    {
        return str_starts_with($stored, self::WRAPPED_PREFIX . '$argon2id$');
    }
}
