<?php
declare(strict_types=1);

namespace Maps;

use Cake\Core\Configure;

/**
 * Which mapping stack the application draws with.
 *
 * The value is the name a deployment writes in `Maps.provider`; the case decides which directory of
 * view elements renders the maps. Adding a provider is a case, a `match` arm and a directory of
 * elements beside `leaflet`.
 */
enum MapProvider: string
{
    /**
     * Leaflet with OpenStreetMap tiles.
     */
    case Osm = 'osm';

    /**
     * The configured provider, falling back to the default so a typo in the environment cannot
     * leave the maps unrendered.
     */
    public static function current(): self
    {
        $provider = Configure::read('Maps.provider');

        return self::tryFrom(is_string($provider) ? strtolower($provider) : '') ?? self::Osm;
    }

    /**
     * Name of the view element subdirectory holding this provider's maps.
     */
    public function elementDirectory(): string
    {
        return match ($this) {
            self::Osm => 'leaflet',
        };
    }
}
