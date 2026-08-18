<?php
declare(strict_types=1);

namespace Maps\Geocoder;

use Cake\Core\Configure;
use RuntimeException;

/**
 * Builds the geocoder the application named in `Maps.geocoder`.
 *
 * Naming none is a fair answer: the maps then offer no address search and a point is picked by
 * clicking the map.
 */
class GeocoderFactory
{
    /**
     * The configured geocoder, or null when the application named none.
     *
     * @return \Maps\Geocoder\GeocoderInterface|null
     */
    public static function create(): ?GeocoderInterface
    {
        $className = Configure::read('Maps.geocoder');

        if (!is_string($className) || $className === '') {
            return null;
        }

        if (!is_subclass_of($className, GeocoderInterface::class)) {
            throw new RuntimeException(
                sprintf('`Maps.geocoder` must name a %s, `%s` is not one.', GeocoderInterface::class, $className),
            );
        }

        return new $className();
    }
}
