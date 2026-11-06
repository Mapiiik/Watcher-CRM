<?php
declare(strict_types=1);

namespace App\Utility;

use Normalizer;

/**
 * Strings
 */
class Strings
{
    /**
     * Remove accents/diacritics from a UTF-8 string.
     *
     * @param string $str Input text
     * @return string ASCII-normalized text
     */
    public static function removeAccents(string $str): string
    {
        // Normalize to decomposed form (base char + diacritic marks)
        $normalized = Normalizer::normalize($str, Normalizer::FORM_D);

        if ($normalized === false) {
            // Fallback: return original string if normalization fails
            return $str;
        }

        // Remove all diacritic marks (Mn = nonspacing marks)
        return preg_replace('/\p{Mn}/u', '', $normalized);
    }

    /**
     * Whether text is a whole number a database integer holds. Asking the database about a larger
     * one is an error, not an empty answer.
     *
     * @param string $text The text.
     * @return bool
     */
    public static function isDatabaseInteger(string $text): bool
    {
        return ctype_digit($text) && strlen($text) <= 10 && (int)$text <= 2147483647;
    }

    /**
     * Generate password with unique characters.
     *
     * @param int $length Length of new password
     * @param non-empty-string $possible Available chars for password
     * @return string
     */
    public static function generatePassword(
        int $length = 8,
        string $possible = '123456789ABCDEFGHJKLMNPQRSTUVWXabcdefghjkmnopqrstuvwx',
    ): string {
        // start with a blank password
        $password = '';

        // set up a counter
        $i = 0;

        // add random characters to $password until $length is reached
        while ($i < $length) {
            // pick a random character from the possible ones
            $char = substr($possible, random_int(0, strlen($possible) - 1), 1);
            // we don't want this character if it's already in the password
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        // done!
        return $password;
    }
}
