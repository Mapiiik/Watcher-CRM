<?php
declare(strict_types=1);

namespace App\NMS\Dto;

/**
 * One access point as the network management system keeps it.
 *
 * Carries what this application asks about a point and nothing else: what to call it, whether it
 * is still in use, where it stands, and which point it hangs off. The network management system
 * knows a good deal more about its own masts, and what it knows is its business - a field is added
 * here when something here starts needing it, and {@see $raw} is what a caller reaches for in the
 * meantime.
 *
 * A point named inside a range or a device arrives with only its number and its name, because that
 * is all those readings carry.
 */
final readonly class AccessPoint
{
    /**
     * @param string $id The number the network management system keeps the point under.
     * @param string|null $name What the point is called.
     * @param string|null $archived When it was put out of use, as the NMS wrote it; null while in use.
     * @param string|null $parentAccessPointId The point this one hangs off.
     * @param float|null $latitude Where the point stands.
     * @param float|null $longitude Where the point stands.
     * @param array<string, mixed> $raw The point as it arrived.
     */
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $archived = null,
        public ?string $parentAccessPointId = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public array $raw = [],
    ) {
    }

    /**
     * Whether the point is still in use.
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->archived !== null;
    }
}
