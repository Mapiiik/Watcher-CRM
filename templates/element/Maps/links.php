<?php
/**
 * External map links for a single pair of coordinates.
 *
 * Renders nothing when the coordinates are missing or not numeric, so callers
 * do not have to guard themselves.
 *
 * @var \App\View\AppView $this
 * @var string|float|null $lat Latitude (gps_y)
 * @var string|float|null $lng Longitude (gps_x)
 * @var string $separator Markup placed between the links
 */

if (!is_numeric($lat) || !is_numeric($lng)) {
    return;
}

$separator = $separator ?? ' ';

echo implode($separator, [
    $this->Html->link(
        __('Google Maps'),
        'https://maps.google.com/maps?q=' . h("{$lat},{$lng}"),
        ['target' => '_blank'],
    ),
    $this->Html->link(
        __('Mapy.cz'),
        'https://mapy.cz/zakladni?source=coor&id=' . h("{$lng},{$lat}"),
        ['target' => '_blank'],
    ),
    $this->Html->link(
        __('OpenStreetMap'),
        'https://www.openstreetmap.org/?zoom=17&mlat=' . h("{$lat}") . '&mlon=' . h("{$lng}"),
        ['target' => '_blank'],
    ),
]);
