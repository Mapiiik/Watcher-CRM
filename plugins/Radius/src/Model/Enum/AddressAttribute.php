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
}
