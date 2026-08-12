<?php
declare(strict_types=1);

namespace App\Phones;

use Cake\Core\Configure;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Formats phone numbers against the region named by Phones.defaultRegion.
 */
final class Formatter
{
    /**
     * Returns the number in the international format the numbers are stored in, or null when the
     * value cannot be read as a valid phone number - the caller decides what to do with it then.
     *
     * @param string $phone A single number.
     * @return string|null
     */
    public static function toInternational(string $phone): ?string
    {
        $parsed = self::parse($phone);

        if ($parsed === null) {
            return null;
        }

        return PhoneNumberUtil::getInstance()->format($parsed, PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * Returns the numbers as they are dialled at home. The international prefix belongs to the
     * region the number is from, so only numbers from the configured region lose it - a foreign
     * number keeps the prefix it cannot be dialled without, and anything that is not a phone
     * number at all is left as it stands.
     *
     * @param string $phones One or more numbers separated by commas.
     * @return string
     */
    public static function toLocal(string $phones): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $region = self::region();

        return implode(', ', array_map(
            static function (string $phone) use ($phoneUtil, $region): string {
                $phone = trim($phone);
                $parsed = self::parse($phone);

                if ($parsed === null || $region === null) {
                    return $phone;
                }

                // Sharing a country code is not being from the same region - +1 is both the
                // United States and Canada, and only the area code tells them apart.
                if ($phoneUtil->getRegionCodeForNumber($parsed) !== $region) {
                    return $phone;
                }

                return str_replace(' ', '', $phoneUtil->format($parsed, PhoneNumberFormat::NATIONAL));
            },
            explode(',', $phones),
        ));
    }

    /**
     * Whether the value can be read as a valid phone number against the configured region.
     *
     * @param string $phone A single number.
     * @return bool
     */
    public static function isValid(string $phone): bool
    {
        return self::parse($phone) !== null;
    }

    /**
     * Reads the number against the configured region, null when it is not a valid one.
     *
     * @param string $phone A single number.
     * @return \libphonenumber\PhoneNumber|null
     */
    private static function parse(string $phone): ?PhoneNumber
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsed = $phoneUtil->parse($phone, self::region());
        } catch (NumberParseException) {
            return null;
        }

        return $phoneUtil->isValidNumber($parsed) ? $parsed : null;
    }

    /**
     * The region numbers without a country prefix are read as, null when none is named.
     *
     * @return string|null
     */
    private static function region(): ?string
    {
        $region = Configure::read('Phones.defaultRegion');

        return is_string($region) && $region !== '' ? $region : null;
    }
}
