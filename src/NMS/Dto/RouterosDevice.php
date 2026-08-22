<?php
declare(strict_types=1);

namespace App\NMS\Dto;

/**
 * One RouterOS device as the network management system keeps it.
 *
 * Two names, and which one to show is the caller's to decide: the system description reads better
 * in a table of addresses, the name in a list of devices.
 */
final readonly class RouterosDevice
{
    /**
     * @param string $id The number the network management system keeps the device under.
     * @param string|null $name What the device is called there.
     * @param string|null $systemDescription What the device calls itself.
     * @param string|null $accessPointId The point the device serves from.
     * @param \App\NMS\Dto\AccessPoint|null $accessPoint The same point, where the reading named it.
     * @param array<string, mixed> $raw The device as it arrived.
     */
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $systemDescription = null,
        public ?string $accessPointId = null,
        public ?AccessPoint $accessPoint = null,
        public array $raw = [],
    ) {
    }
}
