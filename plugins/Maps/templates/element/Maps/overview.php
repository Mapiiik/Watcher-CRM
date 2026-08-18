<?php
/**
 * Read only overview map with coloured markers and lines between points.
 *
 * Dispatches to the provider selected by `Maps.provider`.
 *
 * @var \Cake\View\View $this
 * @var array<string, \Maps\Marker> $mapMarkers
 * @var array<string, \Maps\Polyline> $mapPolylines
 * @var string $mapHeight
 */

use Maps\MapProvider;

echo $this->element('Maps.Maps/' . MapProvider::current()->elementDirectory() . '/overview', [
    'mapMarkers' => $mapMarkers,
    'mapPolylines' => $mapPolylines,
    'mapHeight' => $mapHeight ?? '600px',
]);
