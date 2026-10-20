<?php
declare(strict_types=1);

namespace Access\Authentication;

use Cake\Database\Type\EnumLabelInterface;
use Override;
use Settings\ValueObject\SettingChoices;

/**
 * The hash function one-time codes are made with: one of the three RFC 6238 names.
 *
 * SHA-1 is what every authenticator app understands. Some apps, Microsoft Authenticator among
 * them, read the others from the QR code and quietly make SHA-1 codes all the same, which is found
 * out when the first code is refused.
 */
enum OneTimePasswordAlgorithm: string implements EnumLabelInterface, SettingChoices
{
    case Sha1 = 'SHA1';
    case Sha256 = 'SHA256';
    case Sha512 = 'SHA512';

    /**
     * The algorithm a stored name stands for, SHA-1 where it names none known.
     *
     * @param string|null $name The name, as stored with an account or in the settings.
     * @return self
     */
    public static function of(?string $name): self
    {
        return self::tryFrom((string)$name) ?? self::Sha1;
    }

    /**
     * The name `hash_hmac()` knows it by.
     *
     * @return string
     */
    public function hash(): string
    {
        return strtolower($this->value);
    }

    /**
     * The name people know it by, such as SHA-256.
     *
     * @return string
     */
    public function title(): string
    {
        return substr($this->value, 0, 3) . '-' . substr($this->value, 3);
    }

    /**
     * How long a new secret is, in bytes: as long as the hash, as RFC 6238 has it.
     *
     * @return positive-int
     */
    public function secretBytes(): int
    {
        return match ($this) {
            self::Sha1 => 20,
            self::Sha256 => 32,
            self::Sha512 => 64,
        };
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Sha1 => __d('access', 'SHA-1, which every authenticator app understands'),
            self::Sha256 => __d('access', 'SHA-256, which not every authenticator app understands'),
            self::Sha512 => __d('access', 'SHA-512, which not every authenticator app understands'),
        };
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
