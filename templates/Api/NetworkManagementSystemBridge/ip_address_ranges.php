<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $ipAddressRanges IP Address Ranges
 */

use App\NMS\Links;

if (isset($ipAddressRanges)) {
    $range = $ipAddressRanges->first();
    $accessPointUrl = isset($range['access_point']['id'])
        ? Links::accessPoint((string)$range['access_point']['id'])
        : null;
    echo $accessPointUrl !== null ?
        __('Access Point') . ': ' . $this->Html->link(
            $range['access_point']['name'],
            $accessPointUrl,
            ['target' => '_blank'],
        ) . '<br>' : '';
    $rangeUrl = isset($range['id']) ? Links::ipAddressRange((string)$range['id']) : null;
    echo $rangeUrl !== null ?
        __('Range') . ': ' . $this->Html->link(
            $range['name'],
            $rangeUrl,
            ['target' => '_blank'],
        ) . '<br>' : '';
    unset($range);
}
