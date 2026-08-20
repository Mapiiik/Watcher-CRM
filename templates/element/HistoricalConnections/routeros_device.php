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

echo $this->element('RouterosDevices/link', [
    'id' => $interval->routeros_device_id,
    'name' => $interval->routeros_device_name,
]);
