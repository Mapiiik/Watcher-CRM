<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $routerosDevices RouterOS Devices
 */

if (isset($routerosDevices)) {
    $device = $routerosDevices->first();

    $accessPoint = $this->element('AccessPoints/link', [
        'id' => isset($device['access_point']['id']) ? (string)$device['access_point']['id'] : null,
        'name' => isset($device['access_point']['name']) ? (string)$device['access_point']['name'] : null,
    ]);
    echo $accessPoint !== '' ? __('Access Point') . ': ' . $accessPoint . '<br>' : '';

    $deviceLink = $this->element('RouterosDevices/link', [
        'id' => isset($device['id']) ? (string)$device['id'] : null,
        'name' => isset($device['name']) ? (string)$device['name'] : null,
    ]);
    echo $deviceLink !== '' ? $deviceLink . '<br>' : '';

    unset($device);
}
