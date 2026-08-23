<?php
/**
 * Editable map for picking a single point, rendered with Leaflet.
 *
 * Clicking the map writes the coordinates into the named form fields and moves the marker. The
 * address search is offered only when the application named a geocoder, and it asks the
 * application's own `Api/GeocoderBridge` rather than a geocoder directly.
 *
 * Render this **outside** the `<form>` it fills in, after `Form->end()`. The base layer switcher
 * is a radio group Leaflet names after a counter of its own and writes into the map at runtime,
 * so a form enclosing the map submits fields the form protection never signed and refuses the
 * whole request. The fields are found by id, so where the map stands does not matter otherwise.
 *
 * @var \Cake\View\View $this
 * @var string|float|null $lat
 * @var string|float|null $lng
 * @var string $latField
 * @var string $lngField
 * @var string|null $country
 * @var string $mapHeight
 */

use Cake\Core\Configure;
use Maps\Geocoder\GeocoderFactory;

$this->Html->css(['Maps.vendor/leaflet', 'Maps.maps'], ['block' => true]);
$this->Html->script(
    ['Maps.vendor/leaflet', 'Maps.map-fullscreen', 'Maps.map', 'Maps.point-picker'],
    ['block' => true],
);

$hasPosition = is_numeric($lat) && is_numeric($lng);
$view = (array)Configure::read('Maps.view');

// A point with no coordinates yet still gets a marker, in the middle of the view, so that clicking
// the map or picking a search result has something to move.
$described = [
    'map' => (array)Configure::read('Maps.map'),
    'baseLayers' => array_values((array)Configure::read('Maps.baseLayers')),
    'view' => $hasPosition ? ['lat' => (float)$lat, 'lng' => (float)$lng, 'zoom' => 16] : $view,
    'fullscreen' => [
        'enter' => __d('maps', 'Fullscreen'),
        'exit' => __d('maps', 'Exit Fullscreen'),
    ],
    'markers' => [[
        'lat' => $hasPosition ? (float)$lat : $view['lat'],
        'lng' => $hasPosition ? (float)$lng : $view['lng'],
    ]],
    'polylines' => [],
];

// Without a geocoder there is nothing to search with, so no field is offered.
$searchable = GeocoderFactory::create() !== null;

$picker = [
    'latInputId' => $latField,
    'lngInputId' => $lngField,
    'searchInputId' => 'search-on-the-map',
    'searchUrl' => $searchable
        ? $this->Url->build([
            'prefix' => 'Api',
            'controller' => 'GeocoderBridge',
            'action' => 'search',
            '_ext' => 'json',
        ])
        : null,
    'country' => $country,
];

if ($searchable) {
    echo $this->Form->control('search_on_the_map', [
        'label' => __d('maps', 'Search on the Map'),
    ]);
}
?>
<div class="maps-map" style="height: <?= h($mapHeight) ?>"
     data-maps="<?= h(json_encode($described, JSON_THROW_ON_ERROR)) ?>"
     data-maps-point-picker="<?= h(json_encode($picker, JSON_THROW_ON_ERROR)) ?>"></div>
