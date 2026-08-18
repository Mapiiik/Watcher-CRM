<?php
/**
 * Overview map rendered with Leaflet.
 *
 * The whole map travels as one description in a data attribute, which the script reads once the
 * page is ready.
 *
 * @var \Cake\View\View $this
 * @var array<string, \Maps\Marker> $mapMarkers
 * @var array<string, \Maps\Polyline> $mapPolylines
 * @var string $mapHeight
 */

use Cake\Core\Configure;
use Maps\Marker;
use Maps\Polyline;

$this->Html->css(['Maps.vendor/leaflet', 'Maps.maps'], ['block' => true]);
$this->Html->script(
    ['Maps.vendor/leaflet', 'Maps.map-fullscreen', 'Maps.map'],
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
?>
<div class="maps-map" style="height: <?= h($mapHeight) ?>"
     data-maps="<?= h(json_encode($described, JSON_THROW_ON_ERROR)) ?>"></div>
