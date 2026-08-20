<?php
/**
 * RouterOS device, linked to the network management system that keeps it.
 *
 * Which of the device's names to show is the caller's to decide - the system
 * description reads better in a table of addresses, the name in a list of
 * devices - so the name arrives already chosen.
 *
 * @var \App\View\AppView $this
 * @var string|null $id
 * @var string|null $name
 */

use App\NMS\Links;

if ($name === null || $name === '') {
    return;
}

$url = $id === null ? null : Links::routerosDevice($id);

echo $url !== null ? $this->Html->link($name, $url, ['target' => '_blank']) : h($name);
