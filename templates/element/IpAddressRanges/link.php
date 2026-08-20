<?php
/**
 * Range of IP addresses, linked to the network management system that keeps it.
 *
 * @var \App\View\AppView $this
 * @var string|null $id
 * @var string|null $name
 */

use App\NMS\Links;

if ($name === null || $name === '') {
    return;
}

$url = $id === null ? null : Links::ipAddressRange($id);

echo $url !== null ? $this->Html->link($name, $url, ['target' => '_blank']) : h($name);
