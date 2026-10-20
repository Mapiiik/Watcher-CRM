<?php
declare(strict_types=1);

namespace Access\Authentication;

use ArrayAccess;
use Settings\Utility\Settings;

/**
 * What the settings ask of an account: how long its password has to be and whether it signs in
 * with a second factor.
 *
 * The roles named in `high_security_roles`, and superusers, are held to the stricter rules, as
 * they can see and change the most.
 */
final class SecurityPolicy
{
    public const SETTINGS_PATH = 'access.security';

    /**
     * A setting of the block, or its default where none is stored.
     *
     * @param string $key The setting.
     * @param mixed $default Its default.
     * @return mixed
     */
    public static function setting(string $key, mixed $default): mixed
    {
        return Settings::get(self::SETTINGS_PATH . '.' . $key, $default);
    }

    /**
     * Whether an account is held to the stricter rules.
     *
     * @param \ArrayAccess<string, mixed>|array<string, mixed> $user The account.
     * @return bool
     */
    public static function isHighSecurity(ArrayAccess|array $user): bool
    {
        if (!empty($user['is_superuser'])) {
            return true;
        }

        $roles = (array)self::setting('high_security_roles', ['admin']);

        return isset($user['role']) && in_array($user['role'], $roles, true);
    }

    /**
     * The shortest password an account may be given.
     *
     * @param \ArrayAccess<string, mixed>|array<string, mixed> $user The account.
     * @return int
     */
    public static function minimumPasswordLength(ArrayAccess|array $user): int
    {
        return self::isHighSecurity($user)
            ? self::positive('password_min_length_high_security', 17)
            : self::positive('password_min_length', 12);
    }

    /**
     * Whether passwords that are easy to guess are refused.
     *
     * @return bool
     */
    public static function rejectsSimplePasswords(): bool
    {
        return (bool)self::setting('password_reject_simple', true);
    }

    /**
     * The hash function one-time codes set up from now on are made with. Those already set up
     * keep theirs.
     *
     * @return \Access\Authentication\OneTimePasswordAlgorithm
     */
    public static function oneTimePasswordAlgorithm(): OneTimePasswordAlgorithm
    {
        return OneTimePasswordAlgorithm::of((string)self::setting('two_step_verification_algorithm', 'SHA1'));
    }

    /**
     * Whether an account has to sign in with a one-time code, whether or not it has chosen to.
     *
     * @param \ArrayAccess<string, mixed>|array<string, mixed> $user The account.
     * @return bool
     */
    public static function requiresOneTimePassword(ArrayAccess|array $user): bool
    {
        if (self::setting('two_step_verification_required', false)) {
            return true;
        }

        return self::setting('two_step_verification_required_high_security', false) && self::isHighSecurity($user);
    }

    /**
     * A whole-number setting. Nought or less falls back to the default, as it would leave nothing
     * to enforce.
     *
     * @param string $key The setting.
     * @param int $default Its default.
     * @return int
     */
    public static function positive(string $key, int $default): int
    {
        $value = (int)self::setting($key, $default);

        return $value > 0 ? $value : $default;
    }
}
