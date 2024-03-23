<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $routerosDevices RouterOS Devices
 */

if (isset($routerosDevices)) {
    $device = $routerosDevices->first();
    echo isset($device['id']) ?
        $this->Html->link(
            $device['system_description'],
            env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
            ['target' => '_blank']
        ) . '<br>' : '';
    unset($device);
}
