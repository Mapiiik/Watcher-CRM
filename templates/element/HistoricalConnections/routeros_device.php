<?php
/**
 * RouterOS device the interval was served by.
 *
 * Kept apart from the access point on purpose: the board can be swapped during
 * a service call while the point stays exactly where it was, so the two answer
 * different questions and must not be read as one.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\HistoricalConnection $interval
 */

use App\NMS\Links;

if ($interval->routeros_device_name === null) {
    return;
}

$url = $interval->routeros_device_id === null ? null : Links::routerosDevice($interval->routeros_device_id);

echo $url !== null ? $this->Html->link(
    $interval->routeros_device_name,
    $url,
    ['target' => '_blank'],
) : h($interval->routeros_device_name);
