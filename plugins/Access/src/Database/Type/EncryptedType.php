<?php
declare(strict_types=1);

namespace Access\Database\Type;

use Cake\Core\Exception\CakeException;
use Cake\Database\Driver;
use Cake\Database\Type\StringType;
use Cake\Utility\Security;

/**
 * A string kept encrypted, so that a copy of the database, a backup above all, does not give it
 * away.
 *
 * AES-256-GCM with a random 96-bit nonce and a 128-bit tag, under a key derived from the
 * application's salt. A value that cannot be read, one stored under another salt or changed since,
 * reads as null: whoever uses it decides what that means.
 */
class EncryptedType extends StringType
{
    /**
     * The name the type is mapped by.
     */
    public const NAME = 'access_encrypted';

    /**
     * What a stored value starts with, saying how it was encrypted.
     */
    private const VERSION = 'v1:';

    private const CIPHER = 'aes-256-gcm';

    private const NONCE_BYTES = 12;

    private const TAG_BYTES = 16;

    /**
     * @inheritDoc
     */
    public function toDatabase(mixed $value, Driver $driver): ?string
    {
        $value = parent::toDatabase($value, $driver);

        return $value === null ? null : self::seal($value);
    }

    /**
     * @inheritDoc
     */
    public function toPHP(mixed $value, Driver $driver): ?string
    {
        return $value === null ? null : self::open((string)$value);
    }

    /**
     * Whether a value read has to go through `toPHP()`, which unlike a plain string it does.
     *
     * @return bool
     */
    public function requiresToPhpCast(): bool
    {
        return true;
    }

    /**
     * A value encrypted for storing.
     *
     * @param string $value The value.
     * @return string
     */
    public static function seal(string $value): string
    {
        $nonce = random_bytes(self::NONCE_BYTES);
        $tag = '';
        $encrypted = openssl_encrypt(
            $value,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_BYTES,
        );
        if ($encrypted === false) {
            throw new CakeException('The value could not be encrypted.');
        }

        return self::VERSION . base64_encode($nonce . $tag . $encrypted);
    }

    /**
     * A stored value decrypted, or null where it cannot be.
     *
     * @param string $stored The value as stored.
     * @return string|null
     */
    public static function open(string $stored): ?string
    {
        if (!str_starts_with($stored, self::VERSION)) {
            return null;
        }
        $raw = base64_decode(substr($stored, strlen(self::VERSION)), true);
        if ($raw === false || strlen($raw) < self::NONCE_BYTES + self::TAG_BYTES) {
            return null;
        }

        $value = openssl_decrypt(
            substr($raw, self::NONCE_BYTES + self::TAG_BYTES),
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            substr($raw, 0, self::NONCE_BYTES),
            substr($raw, self::NONCE_BYTES, self::TAG_BYTES),
        );

        return $value === false ? null : $value;
    }

    /**
     * The key, derived from the salt for this use alone.
     *
     * @return string
     */
    private static function key(): string
    {
        return hash_hkdf('sha256', Security::getSalt(), 32, 'Access encrypted column');
    }
}
