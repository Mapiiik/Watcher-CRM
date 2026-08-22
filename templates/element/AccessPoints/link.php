<?php
/**
 * Access point, linked to the network management system that keeps it.
 *
 * The name comes from the NMS either way - looked up as the page is drawn, or written down when a
 * record was made - so it is only ever offered together with the identifier the link needs. An
 * installation with no network management system to point at gets the plain name instead, which is
 * all it can honour.
 *
 * Whoever looked the name up hands over what came of the looking as well. A point that is on the
 * record but has no name to show was then not answered for, and saying so beats an empty cell that
 * reads like no point at all. A name that was written down needs none of that.
 *
 * @var \App\View\AppView $this
 * @var string|null $id
 * @var string|null $name
 * @var \App\Http\Answer<mixed>|null $answer What came of looking the name up, where it was looked up.
 */

use App\NMS\Links;

if ($name === null || $name === '') {
    if (($id ?? '') !== '' && isset($answer)) {
        echo $this->element('NMS/unavailable', ['answer' => $answer]);
    }

    return;
}

$url = $id === null ? null : Links::accessPoint($id);

echo $url !== null ? $this->Html->link($name, $url, ['target' => '_blank']) : h($name);
