<?php
/**
 * Access point the interval took place on.
 *
 * The name is what the NMS reported when the interval was opened and is kept as
 * written, so a point since renamed or removed still reads correctly. The link
 * is only offered where an identifier was recorded, and may still lead nowhere
 * if the point has been removed on the other side since.
 *
 * Left blank when the NMS knew of no point there, which says so more plainly
 * than repeating the address of the network access server would: that has a
 * column of its own everywhere this is used.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ConnectionHistory $interval
 */

if ($interval->access_point_name === null) {
    return;
}

$nmsUrl = (string)env('WATCHER_NMS_URL');

echo $interval->access_point_id !== null && $nmsUrl !== '' ? $this->Html->link(
    $interval->access_point_name,
    $nmsUrl . '/access-points/view/' . $interval->access_point_id,
    ['target' => '_blank'],
) : h($interval->access_point_name);
