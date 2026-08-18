<?php
declare(strict_types=1);

/**
 * What the maps fall back to when the application does not say otherwise.
 *
 * The application's own `Maps` block wins over everything here, so it only has to state what is
 * its own. The environment is never read here - that belongs in the application's `config/app.php`.
 */

return [
    'Maps' => [
        /*
         * Which mapping stack draws the maps. See \Maps\MapProvider for the names.
         */
        'provider' => 'osm',

        /*
         * A \Maps\Geocoder\GeocoderInterface the address search asks, or null for no search at all.
         * The plugin carries two: \Maps\Geocoder\OpenStreetMapGeocoder, which needs nothing but a
         * way out to the internet, and \Maps\Geocoder\AddressRegistryGeocoder, which needs a
         * registry to ask.
         */
        'geocoder' => null,

        /*
         * Photon and Nominatim, which OpenStreetMapGeocoder asks. Both default to the public
         * servers and both are self hostable. The public ones want a User-Agent naming the
         * installation, so an application using them should say who it is.
         *
         * Photon answers in the local language unless `language` names one it carries.
         */
        'photon' => [
            'url' => 'https://photon.komoot.io',
            'language' => null,
        ],
        'nominatim' => [
            'url' => 'https://nominatim.openstreetmap.org',
            'userAgent' => null,
            'referer' => null,
        ],

        /*
         * The national address registry, which AddressRegistryGeocoder asks.
         */
        'addressRegistry' => [
            'url' => null,
            'key' => null,
        ],

        /*
         * Where a map opens when it has nothing of its own to show.
         */
        'view' => [
            'lat' => 49.8,
            'lng' => 15.5,
            'zoom' => 7,
        ],

        /*
         * What the map lets the user do.
         */
        'map' => [
            'scrollWheelZoom' => true,
            'zoomControl' => true,
            'dragging' => true,
        ],

        /*
         * The layer switcher. The first entry is shown on load; `type` is `xyz` or `wms`.
         *
         * OpenStreetMap has no aerial imagery of its own, hence the two extra layers: the Czech
         * cadastral office publishes the national orthophoto as a free WMS, and Esri World Imagery
         * covers the rest. Their attribution is a licence requirement, do not remove it.
         */
        'baseLayers' => [
            [
                'name' => 'OpenStreetMap',
                'type' => 'xyz',
                'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'options' => [
                    'attribution' =>
                        '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    'maxZoom' => 19,
                ],
            ],
            [
                'name' => 'Ortofoto ČR (ČÚZK)',
                'type' => 'wms',
                'url' => 'https://ags.cuzk.cz/arcgis1/services/ORTOFOTO/MapServer/WMSServer',
                'options' => [
                    'layers' => '0',
                    'format' => 'image/jpeg',
                    'transparent' => false,
                    'attribution' => '&copy; <a href="https://cuzk.gov.cz">ČÚZK</a>',
                    'maxZoom' => 20,
                ],
            ],
            [
                'name' => 'Satellite (Esri)',
                'type' => 'xyz',
                'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/'
                    . 'World_Imagery/MapServer/tile/{z}/{y}/{x}',
                'options' => [
                    'attribution' => 'Esri, Maxar, Earthstar Geographics',
                    'maxZoom' => 19,
                ],
            ],
        ],
    ],
];
