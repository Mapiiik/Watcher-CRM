<?php
/**
 * Overview map rendered with Leaflet.
 *
 * The whole map travels as one description in a data attribute, which the script reads once the
 * page is ready. The address search is offered only when asked for and when the application named
 * a geocoder, and it asks the application's own `Api/GeocoderBridge` rather than a geocoder directly.
 *
 * @var \Cake\View\View $this
 * @var array<string, \Maps\Marker> $mapMarkers
 * @var array<string, \Maps\Polyline> $mapPolylines
 * @var string $mapHeight
 * @var bool|null $search
 * @var string|null $country
 */

use Cake\Core\Configure;
use Cake\I18n\I18n;
use Maps\Geocoder\GeocoderFactory;
use Maps\Marker;
use Maps\Polyline;

// Without a geocoder there is nothing to search with, so no field is offered.
$searchable = ($search ?? false) && GeocoderFactory::create() !== null;

// Vendored libraries live whole in `webroot/vendor`, outside the usual `css` and `js`.
$vendor = ['block' => true, 'pathPrefix' => false];
$this->Html->css('Maps.vendor/leaflet/leaflet', $vendor);
$this->Html->script('Maps.vendor/leaflet/leaflet', $vendor);
$this->Html->css('Maps.maps', ['block' => true]);
$this->Html->script(
    $searchable
        ? ['Maps.map-fullscreen', 'Maps.map', 'Maps.address-search', 'Maps.map-search']
        : ['Maps.map-fullscreen', 'Maps.map'],
    ['block' => true],
);

$described = [
    'map' => (array)Configure::read('Maps.map'),
    'baseLayers' => array_values((array)Configure::read('Maps.baseLayers')),
    'view' => ['fit' => true] + (array)Configure::read('Maps.view'),
    'fullscreen' => [
        'enter' => __d('maps', 'Fullscreen'),
        'exit' => __d('maps', 'Exit Fullscreen'),
    ],
    'markers' => array_values(array_map(
        fn(Marker $marker): array => [
            'lat' => $marker->position->lat,
            'lng' => $marker->position->lng,
            'title' => $marker->title,
            'color' => $marker->color,
            'content' => $marker->content,
            'focused' => $marker->focused,
        ],
        $mapMarkers,
    )),
    'polylines' => array_values(array_map(
        fn(Polyline $polyline): array => [
            'from' => $polyline->from->toArray(),
            'to' => $polyline->to->toArray(),
            'options' => $polyline->options,
        ],
        $mapPolylines,
    )),
];

$searching = $searchable ? [
    'searchUrl' => $this->Url->build([
        'prefix' => 'Api',
        'controller' => 'GeocoderBridge',
        'action' => 'search',
        '_ext' => 'json',
    ]),
    'country' => $country ?? null,
    // The distances are written the way the rest of the page is.
    'locale' => str_replace('_', '-', I18n::getLocale()),
    'labels' => [
        'search' => __d('maps', 'Search on the Map'),
        'nearest' => __d('maps', 'Nearest on the Map'),
    ],
] : null;
$searchAttribute = $searching !== null
    ? ' data-maps-search="' . h(json_encode($searching, JSON_THROW_ON_ERROR)) . '"'
    : '';
?>
<div class="maps-map" style="height: <?= h($mapHeight) ?>"
     data-maps="<?= h(json_encode($described, JSON_THROW_ON_ERROR)) ?>"<?= $searchAttribute ?>></div>
