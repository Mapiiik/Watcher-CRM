<?php
use WorkReports\Service\WorkReportSummary;

/**
 * What a month adds up to, the same on the page and in the email.
 *
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReport $workReport
 * @var \WorkReports\Service\WorkReportSummary $summary
 */

$minutes = fn(int $minutes): string => WorkReportSummary::formatMinutes($minutes);
$days_count = fn(int $days): string => __dn('work_reports', '{0} day', '{0} days', $days, $days);
?>
<div class="row">
    <div class="column">
        <table>
            <tr>
                <th><?= __d('work_reports', 'Workload') ?></th>
                <td><?= $this->Number->format($workReport->workload->toFloat()) ?></td>
            </tr>
            <tr>
                <th><?= __d('work_reports', 'Working Days') ?></th>
                <td><?= $this->Number->format($summary->workingDays) ?></td>
            </tr>
            <tr>
                <th><?= __d('work_reports', 'Fund') ?></th>
                <td><?= $minutes($summary->fundMinutes) ?> (<?= $days_count($summary->fundDays) ?>)</td>
            </tr>
            <tr>
                <th><?= __d('work_reports', 'Worked') ?></th>
                <td><?= $minutes($summary->workedMinutes) ?></td>
            </tr>
            <tr>
                <th>
                    <?= $summary->balanceMinutes() >= 0
                        ? __d('work_reports', 'Overtime')
                        : __d('work_reports', 'Hours Missing') ?>
                </th>
                <td><?= $minutes(abs($summary->balanceMinutes())) ?></td>
            </tr>
            <tr>
                <th><?= __d('work_reports', 'Submitted') ?></th>
                <td><?= h($workReport->submitted) ?></td>
            </tr>
        </table>
    </div>
    <div class="column">
        <table>
            <?php foreach ($summary->types as $type) : ?>
            <tr>
                <th><?= h($type['name']) ?></th>
                <td>
                    <?= $type['minutes'] > 0 ? $minutes($type['minutes']) : '' ?>
                    <?= $type['days'] > 0 ? $days_count($type['days']) : '' ?>
                </td>
            </tr>
            <?php endforeach ?>
            <?php foreach ($summary->cars as $car) : ?>
            <tr>
                <th><?= h($car['name']) ?></th>
                <td><?= $this->Number->format($car['distance']) ?> km</td>
            </tr>
            <?php endforeach ?>
            <?php if ($summary->cashCollected != 0) : ?>
            <tr>
                <th><?= __d('work_reports', 'Cash Collected') ?></th>
                <td><?= $this->Number->currency($summary->cashCollected) ?></td>
            </tr>
            <?php endif ?>
            <?php if ($summary->onCallDays > 0) : ?>
            <tr>
                <th><?= __d('work_reports', 'On Call') ?></th>
                <td>
                    <?= $this->Number->format($summary->onCallHours) ?> h
                    (<?= $days_count($summary->onCallDays) ?>)
                </td>
            </tr>
            <?php endif ?>
            <?php foreach ($summary->labels as $label) : ?>
            <tr>
                <th>
                    <span
                        class="app-label"
                        style="<?= $label['label']->style ?>"
                        title="<?= h($label['label']->caption) ?>"
                    ><?= h($label['label']->name) ?></span>
                </th>
                <td><?= $this->Number->format($label['count']) ?>×</td>
            </tr>
            <?php endforeach ?>
        </table>
    </div>
</div>
