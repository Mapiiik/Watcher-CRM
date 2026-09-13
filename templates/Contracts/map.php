<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var array<string, \Maps\Marker> $mapMarkers
 * @var array<string, \Maps\Polyline> $mapPolylines
 * @var float|null $mapDistance
 */
?>
<div class="contracts map content">
    <?= $this->record(__('Contract No.'), (string)$contract->number, doing: __('Map')) ?>
    <?php if ($mapDistance !== null) : ?>
        <p>
            <?= __('Distance') ?>:
            <?= $mapDistance >= 1000
                ? __('{0} km', $this->Number->precision($mapDistance / 1000, 2))
                : __('{0} m', $this->Number->precision($mapDistance, 0)) ?>
        </p>
    <?php endif; ?>
    <?php if ($mapMarkers === []) : ?>
        <p><?= __('Neither end of this contract has coordinates.') ?></p>
    <?php else : ?>
        <?= $this->element('Maps.Maps/overview', [
            'mapMarkers' => $mapMarkers,
            'mapPolylines' => $mapPolylines,
            'mapHeight' => '500px',
        ]) ?>
    <?php endif; ?>
</div>
