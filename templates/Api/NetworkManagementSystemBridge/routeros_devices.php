<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $routerosDevices RouterOS Devices
 */

use App\NMS\Links;

if (isset($routerosDevices)) {
    $device = $routerosDevices->first();
    $deviceUrl = isset($device['id']) ? Links::routerosDevice((string)$device['id']) : null;
    echo $deviceUrl !== null ?
        $this->Html->link(
            $device['system_description'],
            $deviceUrl,
            ['target' => '_blank'],
        ) . '<br>' : '';
    unset($device);
}
