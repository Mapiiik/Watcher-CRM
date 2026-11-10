<?php
declare(strict_types=1);

namespace Radius\Model\Enum;

/**
 * The attributes of `radreply` through which FreeRADIUS hands an account an address.
 */
enum AddressAttribute: string
{
    case FramedIpAddress = 'Framed-IP-Address';
    case FramedIpv6Address = 'Framed-IPv6-Address';
    case FramedRoute = 'Framed-Route';
    case FramedIpv6Prefix = 'Framed-IPv6-Prefix';
    case DelegatedIpv6Prefix = 'Delegated-IPv6-Prefix';

    /**
     * Whether an account is handed one value of it at most. A connection has one address of each
     * family, while any number of networks may be routed to it.
     *
     * @return bool
     */
    public function isSingleValued(): bool
    {
        return match ($this) {
            self::FramedIpAddress, self::FramedIpv6Address => true,
            self::FramedRoute, self::FramedIpv6Prefix, self::DelegatedIpv6Prefix => false,
        };
    }
}
