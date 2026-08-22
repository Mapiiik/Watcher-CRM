<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>> $ipAddressRanges
 */

$range = $ipAddressRanges->data?->first();

if ($range !== null) {
    echo $this->element('IpAddressRanges/summary', ['range' => $range]);
}

echo $this->element('NMS/unavailable', ['answer' => $ipAddressRanges]);
