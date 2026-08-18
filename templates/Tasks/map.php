<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, \Maps\Marker> $mapMarkers
 * @var array<string, \Maps\Polyline> $mapPolylines
 */
?>
<div class="tasks map content">
    <h3><?= __('Tasks') ?></h3>
    <?php if ($mapMarkers === []) : ?>
        <p><?= __('No open task has a place on the map.') ?></p>
    <?php else : ?>
        <?= $this->element('Maps.Maps/overview', [
            'mapMarkers' => $mapMarkers,
            'mapPolylines' => $mapPolylines,
            'mapHeight' => '600px',
        ]) ?>
    <?php endif; ?>
</div>
