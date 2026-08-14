<?php
declare(strict_types=1);

namespace App\BusinessRegister;

/**
 * Where an identification number entered on a customer can be looked up by hand.
 *
 * A link is only offered for a number that holds up, so following one never lands on an empty
 * result. Only ARES takes the number in its address; the Croatian court register keeps the
 * search in a session of its own, so it leads to the search page and the number has to be
 * typed again.
 *
 * A VAT number gets no link at all: what it is and who holds it is asked of the registers and
 * shown on the page itself, and no VAT authority takes a number in its address anyway.
 */
final class PortalLinks
{
    /**
     * The Czech register, which is the only one that takes the number in its address.
     *
     * @var string
     */
    private const ARES = 'https://ares.gov.cz/ekonomicke-subjekty?ico=';

    /**
     * The Croatian court register, which searches from a session of its own.
     *
     * @var string
     */
    private const SUDREG = 'https://sudreg.pravosudje.hr/';

    /**
     * Where the identification number can be looked up, null when it is not one.
     *
     * @param string|null $identityNumber The number as it is stored.
     * @return string|null
     */
    public static function forIdentityNumber(?string $identityNumber): ?string
    {
        if ($identityNumber === null) {
            return null;
        }

        $number = self::digits($identityNumber);

        if (IdentityNumber::isValidCzech($number)) {
            return self::ARES . $number;
        }

        if (IdentityNumber::isValidCroatian($number)) {
            return self::SUDREG;
        }

        return null;
    }

    /**
     * The value without the whitespace it may have been typed with.
     *
     * @param string $value The value as it is stored.
     * @return string
     */
    private static function digits(string $value): string
    {
        return (string)preg_replace('/\s+/', '', $value);
    }
}
