<?php
declare(strict_types=1);

namespace App\SledovaniTV\Dto;

/**
 * One viewer as the television service holds them.
 *
 * Active and suspended are two separate flags, and the debtor blocking turns only the second one.
 * A viewer who was never activated is not one this application suspended, and turning them back on
 * would be handing out a service nobody asked for - so the two are kept apart rather than folded
 * into one verdict.
 *
 * The partner number is the customer's own, as the CRM writes it, which is what ties a viewer back
 * to whoever pays for them.
 */
final readonly class TvUser
{
    /**
     * @param string $id The number the television service keeps the viewer under.
     * @param string|null $partnerNumber The customer's number, as the CRM writes it.
     * @param bool $active Whether the viewer was ever turned on.
     * @param bool $suspended Whether the viewer is currently blocked.
     * @param array<string, mixed> $raw The viewer as they arrived.
     */
    public function __construct(
        public string $id,
        public ?string $partnerNumber = null,
        public bool $active = false,
        public bool $suspended = false,
        public array $raw = [],
    ) {
    }

    /**
     * Whether this viewer is one the blocking may switch off.
     *
     * @return bool
     */
    public function canBeSuspended(): bool
    {
        return $this->active && !$this->suspended;
    }
}
