<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Http\Answer $ipAddressRanges What Watcher NMS said about the ranges the address falls in.
 */

$range = $ipAddressRanges->data?->first();

if ($range !== null) {
    echo $this->element('IpAddressRanges/summary', ['range' => $range]);
}

echo $this->element('NMS/unavailable', ['answer' => $ipAddressRanges]);
