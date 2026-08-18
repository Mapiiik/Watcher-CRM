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
     * Naming several asks each in turn and takes the first answer, which is how a registry that
     * knows one country well is put in front of a map that knows the world roughly.
     *
     * @return \Maps\Geocoder\GeocoderInterface|null
     */
    public static function create(): ?GeocoderInterface
    {
        $named = Configure::read('Maps.geocoder');

        if (is_string($named) && $named !== '') {
            return self::build($named);
        }

        if (!is_array($named) || $named === []) {
            return null;
        }

        $geocoders = array_values(array_map(self::build(...), $named));

        return count($geocoders) === 1 ? $geocoders[0] : new FirstAnsweringGeocoder($geocoders);
    }

    /**
     * Builds one of them, refusing anything that cannot geocode.
     *
     * @param mixed $className What the configuration named.
     * @return \Maps\Geocoder\GeocoderInterface
     */
    private static function build(mixed $className): GeocoderInterface
    {
        if (!is_string($className) || !is_subclass_of($className, GeocoderInterface::class)) {
            throw new RuntimeException(sprintf(
                '`Maps.geocoder` must name a %s, `%s` is not one.',
                GeocoderInterface::class,
                is_string($className) ? $className : get_debug_type($className),
            ));
        }

        return new $className();
    }
}
