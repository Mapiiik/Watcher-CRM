<?php
/**
 * Where an address sits in the other application's addressing.
 *
 * The range and the point it hangs off answer different questions, so both are
 * named where both are known; whatever the NMS said nothing about is left out
 * rather than labelled and left empty.
 *
 * @var \App\View\AppView $this
 * @var \ArrayAccess<string, mixed>|array<string, mixed> $range
 */

$accessPoint = $this->element('AccessPoints/link', [
    'id' => isset($range['access_point']['id']) ? (string)$range['access_point']['id'] : null,
    'name' => isset($range['access_point']['name']) ? (string)$range['access_point']['name'] : null,
]);
if ($accessPoint !== '') {
    echo __('Access Point') . ': ' . $accessPoint . '<br>';
}

$rangeLink = $this->element('IpAddressRanges/link', [
    'id' => isset($range['id']) ? (string)$range['id'] : null,
    'name' => isset($range['name']) ? (string)$range['name'] : null,
]);
if ($rangeLink !== '') {
    echo __('Range') . ': ' . $rangeLink . '<br>';
}
