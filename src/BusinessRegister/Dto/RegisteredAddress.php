<?php
declare(strict_types=1);

namespace App\BusinessRegister\Dto;

/**
 * A place a subject is registered at.
 *
 * The key names the place in the national address registry, in the form an address form is filled
 * in from - so the address is read from the address registry rather than copied out of the business
 * register, and arrives parsed and standardised. A register that hands over no such reference can
 * offer no addresses at all, which is as true of a seat abroad as of a register that knows of no
 * such thing.
 */
final readonly class RegisteredAddress
{
    /**
     * @param string $key The place in the address registry, as "source|reference".
     * @param string|null $label The address on one line, as the business register writes it.
     * @param bool $seat Whether this is the seat rather than somewhere else it trades.
     */
    public function __construct(
        public string $key,
        public ?string $label = null,
        public bool $seat = false,
    ) {
    }
}
