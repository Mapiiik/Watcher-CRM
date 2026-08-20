<?php
/**
 * Access point, linked to the network management system that keeps it.
 *
 * The name comes from the NMS either way - looked up as the page is drawn, or
 * written down when a record was made - so it is only ever offered together with
 * the identifier the link needs. An installation with no network management
 * system to point at gets the plain name instead, which is all it can honour.
 *
 * @var \App\View\AppView $this
 * @var string|null $id
 * @var string|null $name
 */

use App\NMS\Links;

if ($name === null || $name === '') {
    return;
}

$url = $id === null ? null : Links::accessPoint($id);

echo $url !== null ? $this->Html->link($name, $url, ['target' => '_blank']) : h($name);
