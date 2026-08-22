<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>> $routerosDevices
 */

$device = $routerosDevices->data?->first();

if ($device !== null) {
    $deviceLink = $this->element('RouterosDevices/link', [
        'id' => $device->id,
        'name' => $device->systemDescription,
    ]);
    echo $deviceLink !== '' ? $deviceLink . '<br>' : '';
}

// The cell was filled in from here, so leaving it blank would read as an address no device answers
// for rather than as a question nobody answered.
echo $this->element('NMS/unavailable', ['answer' => $routerosDevices]);
