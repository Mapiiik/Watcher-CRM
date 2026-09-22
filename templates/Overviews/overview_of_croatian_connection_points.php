<?php
/**
 * @var \App\View\AppView $this
 * @var list<\App\RegulatoryReporting\ConnectionPoint> $points
 * @var \Cake\I18n\Date $month_to_display
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('month_to_display', [
            'label' => __('Month To Display'),
            'placeholder' => __('YYYY-MM'),
            'type' => 'month',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="overviews index content">
    <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'button float-right']) ?>
    <?= $this->AuthLink->link(
        __('Export'),
        ['_ext' => 'csv', '?' => ['month_to_display' => $month_to_display->i18nFormat('yyyy-MM')]],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->heading(__('Overview of Croatian Customer Connection Points')
        . ' (' . __('Reports for HAKOM') . ')'
        . ' - '
        . $month_to_display->i18nFormat('LLLL yyyy')) ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Address Point') ?></th>
                    <th><?= __('Address') ?></th>
                    <th><?= __('Infrastructure Type') ?></th>
                    <th><?= __('Active Connections') ?></th>
                    <th><?= __('Active Connections (nonbusiness)') ?></th>
                    <th><?= __('Available Connections') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($points as $point) : ?>
                <tr>
                    <td><?= h($point->reportedReference ?? $point->registryReference) ?></td>
                    <td><?= h($point->formattedAddress) ?></td>
                    <td><?= h($point->group) ?></td>
                    <td><?= $this->Number->format($point->activeConnections()) ?></td>
                    <td><?= $this->Number->format($point->activeNonBusinessConnections()) ?></td>
                    <td><?= $this->Number->format($point->availableConnections()) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
