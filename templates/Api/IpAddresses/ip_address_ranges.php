<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $ipAddressRanges IP Address Ranges
 */

if (isset($ipAddressRanges)) {
    $range = $ipAddressRanges->first();
    echo isset($range['access_point']['id']) ?
        __('Access Point') . ': ' . $this->Html->link(
            $range['access_point']['name'],
            env('WATCHER_NMS_URL')
                . '/access-points/view/' . $range['access_point']['id'],
            ['target' => '_blank']
        ) . '<br>' : '';
    echo isset($range['id']) ?
        __('Range') . ': ' . $this->Html->link(
            $range['name'],
            env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
            ['target' => '_blank']
        ) . '<br>' : '';
    unset($range);
}
