<?php
/**
 * @var \App\View\AppView $this
 * @var array<string, list<\App\RegulatoryReporting\Hr\HakomRow>> $forms
 * @var \App\RegulatoryReporting\Hr\HakomQuarterlyReport $report
 * @var int $year
 * @var int $quarter
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('year', [
            'label' => __('Year'),
            'type' => 'number',
            'value' => $year,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('quarter', [
            'label' => __('Quarter'),
            'options' => [1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'],
            'value' => $quarter,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="overviews index content">
    <?= $this->AuthLink->link(__('List Overviews'), ['action' => 'index'], ['class' => 'button float-right']) ?>
    <?= $this->AuthLink->link(
        __('Export'),
        ['_ext' => 'csv', '?' => ['year' => $year, 'quarter' => $quarter]],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->heading(__('Overview of Croatian Quarterly Report')
        . ' (' . __('Reports for HAKOM') . ')'
        . ' - ' . $year . ' Q' . $quarter) ?>

    <p>
        <?= __(
            'Connections as on {0}, revenue without VAT for the quarter, traffic estimated from RADIUS accounting.',
            h($report->until),
        ) ?>
        <?php if ($report->notPlaced > 0) : ?>
            <br>
            <?= __(
                '{0} connections have no row in the form, being slower than 2 Mbit/s'
                . ' or of a technology the form does not place.',
                $report->notPlaced,
            ) ?>
        <?php endif; ?>
        <?php if ($report->trafficNotPlaced > 0) : ?>
            <br>
            <?= __(
                '{0} TB of traffic belongs to contracts without a connection of a known technology in the quarter.',
                $this->Number->precision($report->trafficNotPlaced / 1e12, 2),
            ) ?>
        <?php endif; ?>
    </p>

    <?php foreach ($forms as $form => $rows) : ?>
    <div class="table-responsive">
        <h4><?= h($form) ?></h4>
        <table>
            <thead>
                <tr>
                    <th><?= __('Code') ?></th>
                    <th><?= __('Indicator') ?></th>
                    <th><?= __('Value') ?></th>
                    <th><?= __('Unit') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?= h($row->code) ?></td>
                    <td><?= h($row->label) ?></td>
                    <td><?= is_int($row->value)
                        ? $this->Number->format($row->value)
                        : $this->Number->precision($row->value, 2) ?></td>
                    <td><?= h($row->unit) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
</div>
