<?php
declare(strict_types=1);

namespace Access\Authentication;

use ArrayAccess;

/**
 * What a new password has to be: long enough, and not one that is easy to guess.
 *
 * A password is easy to guess when it contains the account's username or e-mail, when it is a
 * short piece repeated or a run of neighbouring characters, or when it is one of the most common
 * passwords, also with digits and signs added before or after it. The list is the 10,000 most
 * common from SecLists.
 */
final class PasswordRules
{
    /**
     * The most common passwords, lower case, as keys.
     *
     * @var array<string, true>|null
     */
    private static ?array $common = null;

    /**
     * @param int $minimumLength The shortest password allowed.
     * @param bool $rejectSimple Whether passwords that are easy to guess are refused.
     */
    public function __construct(
        private int $minimumLength,
        private bool $rejectSimple = true,
    ) {
    }

    /**
     * The rules the settings set for an account.
     *
     * @param \ArrayAccess<string, mixed>|array<string, mixed> $account The account.
     * @return self
     */
    public static function forAccount(ArrayAccess|array $account): self
    {
        return new self(
            SecurityPolicy::minimumPasswordLength($account),
            SecurityPolicy::rejectsSimplePasswords(),
        );
    }

    /**
     * What is wrong with a password, if anything.
     *
     * @param string $password The password.
     * @param string|null $username The username of the account.
     * @param string|null $email The e-mail of the account.
     * @return list<string>
     */
    public function problems(string $password, ?string $username = null, ?string $email = null): array
    {
        $problems = [];
        if (mb_strlen($password) < $this->minimumLength) {
            $problems[] = __d('access', 'The password has to have at least {0} characters.', $this->minimumLength);
        }
        if (!$this->rejectSimple) {
            return $problems;
        }

        $lower = mb_strtolower($password);
        foreach ($this->personal($username, $email) as $part) {
            if (str_contains($lower, $part)) {
                $problems[] = __d('access', 'The password must not contain the username or the e-mail.');
                break;
            }
        }
        if ($this->isPattern($lower)) {
            $problems[] = __d('access', 'The password must not be a repeated piece or a run of characters.');
        }
        if ($this->isCommon($lower)) {
            $problems[] = __d('access', 'The password is one of the most common ones.');
        }

        return $problems;
    }

    /**
     * The parts of the account a password must not contain.
     *
     * @param string|null $username The username.
     * @param string|null $email The e-mail.
     * @return list<string>
     */
    private function personal(?string $username, ?string $email): array
    {
        $parts = [(string)$username, (string)$email, strstr((string)$email, '@', true) ?: ''];

        return array_values(array_filter(
            array_map('mb_strtolower', $parts),
            fn(string $part): bool => mb_strlen($part) >= 3,
        ));
    }

    /**
     * Whether a password is a short piece repeated, or a run of characters each next to the last.
     *
     * @param string $password The password, lower case.
     * @return bool
     */
    private function isPattern(string $password): bool
    {
        if (preg_match('/^(.{1,4})\1+$/us', $password) === 1) {
            return true;
        }

        $codes = array_map('mb_ord', mb_str_split($password));
        if (count($codes) < 3) {
            return false;
        }
        $step = $codes[1] - $codes[0];
        if (abs($step) !== 1) {
            return false;
        }
        $length = count($codes);
        for ($i = 2; $i < $length; $i++) {
            if ($codes[$i] - $codes[$i - 1] !== $step) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a password is one of the most common, as it is or with digits and signs around it.
     *
     * @param string $password The password, lower case.
     * @return bool
     */
    private function isCommon(string $password): bool
    {
        $common = self::common();
        $word = preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $password) ?? $password;

        return isset($common[$password]) || (mb_strlen($word) >= 4 && isset($common[$word]));
    }

    /**
     * @return array<string, true>
     */
    private static function common(): array
    {
        if (self::$common === null) {
            $lines = file(dirname(__DIR__, 2) . '/resources/common-passwords.txt', FILE_IGNORE_NEW_LINES);
            self::$common = array_fill_keys(array_map('mb_strtolower', $lines ?: []), true);
        }

        return self::$common;
    }
}
