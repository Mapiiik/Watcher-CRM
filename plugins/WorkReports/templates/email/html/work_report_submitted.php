<?php
use WorkReports\Service\WorkReportSummary;

/**
 * A submitted work report, for the worker and whoever gets their reports.
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var \WorkReports\Model\Entity\WorkReport $workReport
 * @var \WorkReports\Service\WorkReportSummary $summary
 */

$this->assign('title', $title);
?>
<h2><?= $this->fetch('title') ?></h2>
<?= $this->element('WorkReports.summary', compact('workReport', 'summary')) ?>
<?php if ($summary->extraDays !== []) : ?>
    <p>
        <?= __d(
            'work_reports',
            'Also reported on {0}.',
            implode(', ', array_map(fn($day): string => (string)$day->i18nFormat('d. M.'), $summary->extraDays)),
        ) ?>
    </p>
<?php endif ?>
<?= $this->Html->link(
    __d('work_reports', 'View Work Report'),
    [
        'plugin' => 'WorkReports',
        'controller' => 'WorkReports',
        'action' => 'sheet',
        '?' => ['user_id' => $workReport->user_id, 'month' => $workReport->month->format('Y-m')],
        '_full' => true,
    ],
) ?>
<table>
    <tr>
        <th><?= __d('work_reports', 'Date') ?></th>
        <th><?= __d('work_reports', 'Work From') ?></th>
        <th><?= __d('work_reports', 'Work Until') ?></th>
        <th><?= __d('work_reports', 'Hours') ?></th>
        <th><?= __d('work_reports', 'Work Report Item Type') ?></th>
        <th><?= __d('work_reports', 'Description') ?></th>
        <th><?= __d('work_reports', 'Customer') ?></th>
        <th><?= __d('work_reports', 'Distance') ?></th>
    </tr>
    <?php foreach ($workReport->work_report_items as $item) : ?>
    <tr>
        <td><?= h($item->date->i18nFormat('EEE d. M.')) ?></td>
        <?php if ($item->whole_day) : ?>
            <td colspan="3"><?= __d('work_reports', 'whole day') ?></td>
        <?php else : ?>
            <td><?= h($item->time_from) ?></td>
            <td><?= h($item->time_until) ?></td>
            <td><?= WorkReportSummary::formatMinutes($item->minutes) ?></td>
        <?php endif ?>
        <td><?= h($item->work_report_item_type->name) ?></td>
        <td><?= nl2br(h((string)$item->description)) ?></td>
        <td><?= h($item->customer?->name) ?></td>
        <td>
            <?= $item->private_car_distance ? $this->Number->format($item->private_car_distance) . ' km' : '' ?>
            <?= $item->company_car_distance ? $this->Number->format($item->company_car_distance) . ' km' : '' ?>
        </td>
    </tr>
    <?php endforeach ?>
</table>
