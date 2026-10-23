<?php
declare(strict_types=1);

namespace App\Agent\Dto;

/**
 * One address on a firewall address list of a router.
 */
final readonly class AddressListEntry
{
    /**
     * @param string $address Address or network, as the router or the request wrote it.
     * @param string $family `ipv4` or `ipv6`.
     * @param string $comment What the entry says about whose the address is.
     */
    public function __construct(
        public string $address,
        public string $family,
        public string $comment = '',
    ) {
    }

    /**
     * Whether the entry is on the IPv6 list.
     *
     * @return bool
     */
    public function isIpv6(): bool
    {
        return $this->family === 'ipv6';
    }
}
