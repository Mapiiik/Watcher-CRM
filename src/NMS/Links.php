<?php
declare(strict_types=1);

namespace App\NMS;

use Cake\Core\Configure;

/**
 * Where a thing of the network management system's is to be found.
 *
 * The paths belong to the other application and change with it, so they are written here once
 * rather than wherever a link happens to be wanted. Nothing is offered when this installation has
 * no network management system to point at - a caller that gets nothing shows the plain name
 * instead, which is the honest answer. Building the path regardless would point the link back at
 * this application, at an address that does not exist here.
 */
class Links
{
    /**
     * The application itself.
     */
    public static function home(): ?string
    {
        return self::to('');
    }

    /**
     * An access point.
     */
    public static function accessPoint(string $id): ?string
    {
        return self::to('/access-points/' . $id);
    }

    /**
     * A RouterOS device.
     */
    public static function routerosDevice(string $id): ?string
    {
        return self::to('/routeros-devices/view/' . $id);
    }

    /**
     * A range of IP addresses.
     */
    public static function ipAddressRange(string $id): ?string
    {
        return self::to('/ip-address-ranges/view/' . $id);
    }

    /**
     * Puts a path behind the address of the network management system.
     */
    private static function to(string $path): ?string
    {
        $url = Configure::read('Nms.url');

        return is_string($url) && $url !== '' ? rtrim($url, '/') . $path : null;
    }
}
