<?php
/**
 * Editable map for picking a single point.
 *
 * Clicking the map writes the coordinates into the form fields and moves the marker. Dispatches to
 * the provider selected by `Maps.provider`.
 *
 * @var \Cake\View\View $this
 * @var string|float|null $lat
 * @var string|float|null $lng
 * @var string|null $latField Id of the field taking the latitude
 * @var string|null $lngField Id of the field taking the longitude
 * @var string|null $country ISO country code to search addresses within
 * @var string|null $mapHeight
 */

use Maps\MapProvider;

echo $this->element('Maps.Maps/' . MapProvider::current()->elementDirectory() . '/point-picker', [
    'lat' => $lat,
    'lng' => $lng,
    'latField' => $latField ?? 'gps-y',
    'lngField' => $lngField ?? 'gps-x',
    'country' => $country ?? null,
    'mapHeight' => $mapHeight ?? '400px',
]);
