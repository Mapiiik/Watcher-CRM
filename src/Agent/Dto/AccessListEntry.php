<?php
declare(strict_types=1);

namespace App\Agent\Dto;

/**
 * One station taken off the access list of an access point.
 */
final readonly class AccessListEntry
{
    /**
     * @param string $menu The access list it was on, which depends on the wireless package.
     * @param string $interface The interface the entry was for, empty for any.
     * @param string $macAddress The station.
     */
    public function __construct(
        public string $menu,
        public string $interface,
        public string $macAddress,
    ) {
    }
}
