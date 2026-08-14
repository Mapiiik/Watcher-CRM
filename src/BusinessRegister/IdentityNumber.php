<?php
declare(strict_types=1);

namespace App\BusinessRegister;

/**
 * Reads identification numbers against the check digit their country gives them.
 *
 * Without a country to go by the number is judged by its own shape, which is as far as the
 * digits can be taken: an eight digit number is read as Czech and an eleven digit one as
 * Croatian, and a number valid in one country is not asked about the other.
 */
final class IdentityNumber
{
    /**
     * Whether the number holds up, either against the named country or against whichever
     * country its length points at.
     *
     * @param string|null $number The number as it was entered.
     * @param string|null $countryCode ISO 3166-1 alpha-2, null to go by the length.
     * @return bool
     */
    public static function isValid(?string $number, ?string $countryCode = null): bool
    {
        if ($number === null) {
            return false;
        }

        return match (strtoupper((string)$countryCode)) {
            'CZ' => self::isValidCzech($number),
            'HR' => self::isValidCroatian($number),
            default => self::isValidCzech($number) || self::isValidCroatian($number),
        };
    }

    /**
     * Whether the number holds up as a Czech identification number (IČO).
     *
     * @param string $number The number as it was entered.
     * @return bool
     */
    public static function isValidCzech(string $number): bool
    {
        $number = self::digits($number);

        // must be exactly 8 digits
        if (!preg_match('/^\d{8}$/', $number)) {
            return false;
        }

        // calculate checksum using weights 8–2 for the first 7 digits
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$number[$i] * (8 - $i);
        }

        // determine check digit based on modulo 11
        $mod = $sum % 11;
        $checkDigit = match ($mod) {
            0 => 1,
            1 => 0,
            default => 11 - $mod,
        };

        // last digit must equal the calculated check digit
        return (int)$number[7] === $checkDigit;
    }

    /**
     * Whether the number holds up as a Croatian identification number (OIB).
     *
     * @param string $number The number as it was entered.
     * @return bool
     */
    public static function isValidCroatian(string $number): bool
    {
        $number = self::digits($number);

        // must be exactly 11 digits
        if (!preg_match('/^\d{11}$/', $number)) {
            return false;
        }

        // ISO 7064 Mod 11,10 algorithm
        $control = 10;
        for ($i = 0; $i < 10; $i++) {
            $digit = (int)$number[$i];
            $control = ($control + $digit) % 10;
            if ($control === 0) {
                $control = 10;
            }
            $control = ($control * 2) % 11;
        }

        $checkDigit = 11 - $control;
        if ($checkDigit === 10) {
            $checkDigit = 0;
        }

        // last digit must equal the calculated check digit
        return (int)$number[10] === $checkDigit;
    }

    /**
     * The number without the whitespace it may have been typed with.
     *
     * @param string $number The number as it was entered.
     * @return string
     */
    private static function digits(string $number): string
    {
        return (string)preg_replace('/\s+/', '', $number);
    }
}
