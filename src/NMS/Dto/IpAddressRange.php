<?php
declare(strict_types=1);

namespace App\NMS\Dto;

/**
 * One range of addresses as the network management system keeps it.
 *
 * The point a range hangs off is named twice over: by its number, which every range carries, and
 * as a point of its own, which only the readings that were asked to expand it carry. A caller that
 * wants the name takes the second and falls back to nothing rather than to the number.
 */
final readonly class IpAddressRange
{
    /**
     * @param string $id The number the network management system keeps the range under.
     * @param string|null $name What the range is called.
     * @param string|null $network The range itself, in CIDR.
     * @param string|null $gateway The address the range is routed through.
     * @param string|null $accessPointId The point the range hangs off.
     * @param \App\NMS\Dto\AccessPoint|null $accessPoint The same point, where the reading named it.
     * @param array<string, mixed> $raw The range as it arrived.
     */
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $network = null,
        public ?string $gateway = null,
        public ?string $accessPointId = null,
        public ?AccessPoint $accessPoint = null,
        public array $raw = [],
    ) {
    }
}
