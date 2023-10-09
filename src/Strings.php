<?php
declare(strict_types=1);

namespace App;

/**
 * Strings
 */
class Strings
{
    /**
     * Normalize non-ASCII characters to ASCII counterparts where possible.
     *
     * @param string $str Text with non-ASCII characters
     * @return string
     */
    public static function removeAccents(string $str): string
    {
        static $normalizeChars = null;

        if ($normalizeChars === null) {
            $normalizeChars = [
                'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'Ae',
                'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
                'Č' => 'C', 'Ç' => 'C',
                'č' => 'c', 'ç' => 'c',
                'Ď' => 'D', 'Ð' => 'Dj',
                'ď' => 'd',
                'Ě' => 'E', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                'ě' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'Ň' => 'N', 'Ñ' => 'N',
                'ň' => 'n', 'ñ' => 'n',
                'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ð' => 'o',
                'Ř' => 'R',
                'ř' => 'r',
                'Š' => 'S', 'Ś' => 'S',
                'š' => 's', 'ś' => 's',
                'Ť' => 'T',
                'ť' => 't',
                'Ů' => 'U', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
                'ů' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'Ý' => 'Y', 'Ÿ' => 'Y',
                'ý' => 'y', 'ÿ' => 'y',
                'Ž' => 'Z',
                'ž' => 'z',
                'Þ' => 'B',
                'ß' => 'Ss',
                'þ' => 'b',
                'ƒ' => 'f',
            ];
        }

        return strtr($str, $normalizeChars);
    }

    /**
     * Generate password.
     *
     * @param int $length Length of new password
     * @param string $possible Available chars for password
     * @return string
     */
    public static function generatePassword(
        int $length = 8,
        string $possible = '123456789ABCDEFGHJKLMNPQRSTUVWXabcdefghjkmnopqrstuvwx'
    ): string {
        // start with a blank password
        $password = '';

        // set up a counter
        $i = 0;

        // add random characters to $password until $length is reached
        while ($i < $length) {
            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
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
