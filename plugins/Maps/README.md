# Maps

Maps for a CakePHP application: a read only overview of markers and the lines between them, and an
editable picker for a single point.

## What the application provides

```php
// config/plugins.php
'Maps' => [],
```

```php
// config/app.php - only what is this application's own; the rest comes from config/maps.php here
'Maps' => [
    'provider' => env('MAP_PROVIDER', 'osm'),
    'geocoder' => YourGeocoder::class,
],
```

Its assets are served out of this plugin's webroot, which asks a deployment for
`bin/cake plugin assets symlink` and nothing else.

## Drawing a map

```php
echo $this->element('Maps.Maps/overview', [
    'mapMarkers' => $mapMarkers,     // array<string, \Maps\Marker>
    'mapPolylines' => $mapPolylines, // array<string, \Maps\Polyline>
]);

echo $this->element('Maps.Maps/point-picker', [
    'lat' => $entity->gps_y,
    'lng' => $entity->gps_x,
]);
```

The picker writes into the fields named by `latField` and `lngField`, which default to `gps-y` and
`gps-x`.

## The address search

Offered only when `Maps.geocoder` names a `\Maps\Geocoder\GeocoderInterface`. Without one the picker
renders no search field and a point is picked by clicking the map.

Two come with the plugin, and an application may bring its own:

- `\Maps\Geocoder\OpenStreetMapGeocoder` - Photon for the search, Nominatim for the reverse lookup.
  Needs nothing but a way out to the internet. Configured under `Maps.photon` and `Maps.nominatim`.
- `\Maps\Geocoder\AddressRegistryGeocoder` - a national address registry, which is official data
  rather than a crowd sourced map, and knows one country at a time. Configured under
  `Maps.addressRegistry`; the picker must be told which `country` to search.

The browser never asks the geocoder itself - it asks the application, so that a geocoder reached
with a key can be used without the key leaving the server. The application answers with a controller
of its own:

```php
// src/Controller/Api/GeocoderBridgeController.php
class GeocoderBridgeController extends AppController
{
    use \Maps\Controller\Trait\GeocoderBridgeControllerTrait;
}
```

routed as a resource with a `search` action under the `Api` prefix, which is the URL the picker
builds.

## Adding a map provider

Only OpenStreetMap through Leaflet is built in. Another one is:

1. a case in `\Maps\MapProvider` and an arm in its `elementDirectory()`,
2. `templates/element/Maps/<directory>/overview.php` and `point-picker.php` beside `leaflet`,
3. whatever assets it needs in `webroot`.

Nothing else dispatches on the provider.

## Leaflet

Leaflet 1.9.4 is vendored in `webroot/css/vendor` and `webroot/js/vendor`, unchanged, so that a
map needs nothing from a CDN. Upgrading is replacing those files - and doing it in every application carrying a copy
of this plugin.
