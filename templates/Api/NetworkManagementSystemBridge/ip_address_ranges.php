<?php
/**
 * @var \App\View\AppView $this
 * \Cake\Collection\CollectionInterface|null $ipAddressRanges IP Address Ranges
 */

if (isset($ipAddressRanges)) {
    echo $this->element('IpAddressRanges/summary', ['range' => $ipAddressRanges->first()]);
}
