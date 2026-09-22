<?php
use App\RegulatoryReporting\Cz\CtuActiveSpeedBand;

/**
 * @var \App\View\AppView $this
 * @var array<string, list<\App\RegulatoryReporting\Cz\CtuConnectionPointRow>> $cto_categories
 * @var \Cake\I18n\Date $month_to_display
 */

$count = fn(?int $number): string => $number === null ? '' : $this->Number->format($number);
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
    <?php foreach ($cto_categories as $cto_category => $_connection_points) : ?>
        <?= $this->AuthLink->link(
            __('Export') . ' ' . $cto_category,
            [
                '_ext' => 'csv',
                $cto_category,
                '?' => ['month_to_display' => $month_to_display->i18nFormat('yyyy-MM')],
            ],
            ['class' => 'button float-right'],
        ) ?>
    <?php endforeach; ?>
    <?= $this->heading(__('Overview of Czech Customer Connection Points')
        . ' (' . __('Reports for CTO') . ')'
        . ' - '
        . $month_to_display->i18nFormat('LLLL yyyy')) ?>

    <?php foreach ($cto_categories as $cto_category => $connection_points) : ?>
    <div class="table-responsive">
    <h4><?= $cto_category ?></h4>
        <table>
            <thead>
                <tr>
                    <th><?= __('RUIAN GID') ?></th>
                    <th><?= __('Active Connections') ?></th>
                    <th><?= __('Active Connections (nonbusiness)') ?></th>
                    <th><?= __('Active 0-30 Mbps') ?></th>
                    <th><?= __('Active 30-100 Mbps') ?></th>
                    <th><?= __('Active 100+ Mbps') ?></th>
                    <th><?= __('Available Connections') ?></th>
                    <th><?= __('Available Effective Download Speed Category') ?></th>
                    <th><?= __('Available Effective Upload Speed Category') ?></th>
                    <th><?= __('Available Maximal Download Speed Category') ?></th>
                    <th><?= __('Available Maximal Upload Speed Category') ?></th>
                    <th><?= __('VHCN Network Category') ?></th>
                    <th><?= __('Address') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($connection_points as $row) : ?>
                <tr>
                    <td><?= h($row->reference) ?></td>
                    <td><?= $count($row->activeConnections) ?></td>
                    <td><?= $count($row->activeNonBusinessConnections) ?></td>
                    <td><?= $count($row->activeIn(CtuActiveSpeedBand::Below30)) ?></td>
                    <td><?= $count($row->activeIn(CtuActiveSpeedBand::From30To100)) ?></td>
                    <td><?= $count($row->activeIn(CtuActiveSpeedBand::From100)) ?></td>
                    <td><?= $count($row->availableConnections) ?></td>
                    <td><?= h($row->effectiveDownload->value) ?></td>
                    <td><?= h($row->effectiveUpload->value) ?></td>
                    <td><?= h($row->maximalDownload->value) ?></td>
                    <td><?= h($row->maximalUpload->value) ?></td>
                    <td><?= $count((int)$row->vhcn) ?></td>
                    <td><?= h($row->address) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
