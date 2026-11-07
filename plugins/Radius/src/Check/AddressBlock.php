<?php
declare(strict_types=1);

namespace Radius\Check;

use Stringable;

/**
 * An address or a network, read the same way however it was written.
 *
 * `radreply` holds text and the contracts hold `inet` and `cidr`, so two spellings of one IPv6
 * address have to be told to be one, and an address has to be found inside a network.
 */
final class AddressBlock implements Stringable
{
    /**
     * @param string $bytes The address, in the form `inet_pton()` gives.
     * @param int $prefix How many of its bits are fixed.
     */
    private function __construct(
        private readonly string $bytes,
        private readonly int $prefix,
    ) {
    }

    /**
     * Read what was written, null where it is no address. A route may go on with a gateway and a
     * metric, and a plain address is a block of one.
     *
     * @param string $written The address or network.
     * @return self|null
     */
    public static function read(string $written): ?self
    {
        $parts = explode('/', (string)strtok(trim($written), ' '), 2);

        if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $bytes = (string)inet_pton($parts[0]);
        $bits = strlen($bytes) * 8;
        $prefix = isset($parts[1]) ? (int)$parts[1] : $bits;

        return $prefix >= 0 && $prefix <= $bits ? new self($bytes, $prefix) : null;
    }

    /**
     * Whether all of this block lies inside the other one.
     *
     * @param self $other The block that may hold it.
     * @return bool
     */
    public function isWithin(self $other): bool
    {
        if (strlen($this->bytes) !== strlen($other->bytes) || $this->prefix < $other->prefix) {
            return false;
        }

        $whole = intdiv($other->prefix, 8);
        if (substr($this->bytes, 0, $whole) !== substr($other->bytes, 0, $whole)) {
            return false;
        }

        $rest = $other->prefix % 8;
        if ($rest === 0) {
            return true;
        }

        $mask = 0xff << 8 - $rest & 0xff;

        return (ord($this->bytes[$whole]) & $mask) === (ord($other->bytes[$whole]) & $mask);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $address = (string)inet_ntop($this->bytes);

        return $this->prefix === strlen($this->bytes) * 8 ? $address : $address . '/' . $this->prefix;
    }
}
