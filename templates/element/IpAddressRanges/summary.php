<?php
/**
 * Where an address sits in the other application's addressing.
 *
 * The range and the point it hangs off answer different questions, so both are named where both
 * are known; whatever the NMS said nothing about is left out rather than labelled and left empty.
 *
 * @var \App\View\AppView $this
 * @var \App\NMS\Dto\IpAddressRange $range
 */

$accessPoint = $this->element('AccessPoints/link', [
    'id' => $range->accessPoint?->id,
    'name' => $range->accessPoint?->name,
]);
if ($accessPoint !== '') {
    echo __('Access Point') . ': ' . $accessPoint . '<br>';
}

$rangeLink = $this->element('IpAddressRanges/link', [
    'id' => $range->id,
    'name' => $range->name,
]);
if ($rangeLink !== '') {
    echo __('Range') . ': ' . $rangeLink . '<br>';
}
