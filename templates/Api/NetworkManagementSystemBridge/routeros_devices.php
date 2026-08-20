<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $routerosDevices RouterOS Devices
 */

if (isset($routerosDevices)) {
    $device = $routerosDevices->first();
    $deviceLink = $this->element('RouterosDevices/link', [
        'id' => isset($device['id']) ? (string)$device['id'] : null,
        'name' => isset($device['system_description']) ? (string)$device['system_description'] : null,
    ]);
    echo $deviceLink !== '' ? $deviceLink . '<br>' : '';
    unset($device);
}
