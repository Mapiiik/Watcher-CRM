<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $routerosDevices RouterOS Devices
 */

use App\NMS\Links;

if (isset($routerosDevices)) {
    $device = $routerosDevices->first();
    $accessPointUrl = isset($device['access_point']['id'])
        ? Links::accessPoint((string)$device['access_point']['id'])
        : null;
    echo $accessPointUrl !== null ?
        __('Access Point') . ': ' . $this->Html->link(
            $device['access_point']['name'],
            $accessPointUrl,
            ['target' => '_blank'],
        ) . '<br>' : '';
    $deviceUrl = isset($device['id']) ? Links::routerosDevice((string)$device['id']) : null;
    echo $deviceUrl !== null ?
        $this->Html->link(
            $device['name'],
            $deviceUrl,
            ['target' => '_blank'],
        ) . '<br>' : '';
    unset($device);
}
