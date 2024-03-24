<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $routerosDevices RouterOS Devices
 */

if (isset($routerosDevices)) {
    $device = $routerosDevices->first();
    echo isset($device['access_point']['id']) ?
        __('Access Point') . ': ' . $this->Html->link(
            $device['access_point']['name'],
            env('WATCHER_NMS_URL') . '/access-points/view/' . $device['access_point']['id'],
            ['target' => '_blank']
        ) . '<br>' : '';
    echo isset($device['id']) ?
        $this->Html->link(
            $device['name'],
            env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
            ['target' => '_blank']
        ) . '<br>' : '';
    unset($device);
}
