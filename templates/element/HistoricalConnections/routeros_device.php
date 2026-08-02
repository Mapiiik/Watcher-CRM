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

if ($interval->routeros_device_name === null) {
    return;
}

$nmsUrl = (string)env('WATCHER_NMS_URL');

echo $interval->routeros_device_id !== null && $nmsUrl !== '' ? $this->Html->link(
    $interval->routeros_device_name,
    $nmsUrl . '/routeros-devices/view/' . $interval->routeros_device_id,
    ['target' => '_blank'],
) : h($interval->routeros_device_name);
