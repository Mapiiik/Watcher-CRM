<?php
declare(strict_types=1);

namespace App\Model\Validation;

use IPLib\Factory;
use IPLib\Range\Subnet;

/**
 * What the network types of the database take, so that a typing error is told beside its field
 * rather than on an error page.
 *
 * The addresses have to be written out in full. PostgreSQL would also take a shortened `192.168/24`,
 * but it never hands one back and nobody here writes them.
 */
final class NetworkValidation
{
    /**
     * An address, with a mask or without, as an `inet` column takes it.
     *
     * @param mixed $value The value.
     * @return bool
     */
    public static function inet(mixed $value): bool
    {
        return Factory::parseAddressString($value) !== null || Subnet::parseString($value) !== null;
    }

    /**
     * The address of a network, as a `cidr` column takes it: nothing may be set beyond the mask.
     *
     * @param mixed $value The value.
     * @return bool
     */
    public static function cidr(mixed $value): bool
    {
        if (Factory::parseAddressString($value) !== null) {
            return true;
        }

        $network = Subnet::parseString($value);
        if ($network === null) {
            return false;
        }

        $address = Factory::parseAddressString(explode('/', (string)$value)[0]);

        return $network->getStartAddress()->getComparableString() === $address?->getComparableString();
    }

    /**
     * A hardware address, in any of the ways a `macaddr` column takes it.
     *
     * @param mixed $value The value.
     * @return bool
     */
    public static function macaddr(mixed $value): bool
    {
        return is_string($value) && preg_match(
            '/^(?:[0-9a-f]{2}(?:([:-])[0-9a-f]{2})(?:\1[0-9a-f]{2}){4}'
            . '|[0-9a-f]{6}[:-][0-9a-f]{6}'
            . '|[0-9a-f]{4}([.-])[0-9a-f]{4}\2[0-9a-f]{4}'
            . '|[0-9a-f]{12})$/iD',
            $value,
        ) === 1;
    }
}
