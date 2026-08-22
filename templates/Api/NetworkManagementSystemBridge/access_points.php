<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>> $routerosDevices
 */

$device = $routerosDevices->data?->first();

if ($device !== null) {
    $accessPoint = $this->element('AccessPoints/link', [
        'id' => $device->accessPoint?->id,
        'name' => $device->accessPoint?->name,
    ]);
    echo $accessPoint !== '' ? __('Access Point') . ': ' . $accessPoint . '<br>' : '';

    $deviceLink = $this->element('RouterosDevices/link', [
        'id' => $device->id,
        'name' => $device->name,
    ]);
    echo $deviceLink !== '' ? $deviceLink . '<br>' : '';
}

echo $this->element('NMS/unavailable', ['answer' => $routerosDevices]);
