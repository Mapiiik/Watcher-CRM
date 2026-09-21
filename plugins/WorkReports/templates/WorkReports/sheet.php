<?php
use Cake\I18n\Date;
use WorkReports\Service\WorkReportSummary;

/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReport $workReport
 * @var \WorkReports\Service\WorkReportSummary $summary
 * @var \WorkReports\Service\WorkingCalendar $calendar
 * @var array<string, array{date: \Cake\I18n\Date, items: list<\WorkReports\Model\Entity\WorkReportItem>}> $days
 * @var list<array{value: string, text: string, style: string|null}> $workers
 * @var string $workerName
 * @var \Cake\I18n\Date $month
 */

$minutes = fn(int $minutes): string => WorkReportSummary::formatMinutes($minutes);
$monthUrl = fn(Date $to): array => [
    'action' => 'sheet',
    '?' => ['user_id' => $workReport->user_id, 'month' => $to->format('Y-m')],
];
$addUrl = fn(Date $day): array => [
    'controller' => 'WorkReportItems',
    'action' => 'add',
    '?' => ['user_id' => $workReport->user_id, 'date' => $day->format('Y-m-d')],
];
$days_count = fn(int $days): string => __dn('work_reports', '{0} day', '{0} days', $days, $days);
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if (!$workReport->isLocked()) : ?>
                <?= $this->AuthLink->link(
                    __d('work_reports', 'New Work Report Item'),
                    $addUrl($month),
                    ['class' => 'side-nav-item win-link'],
                ) ?>
            <?php endif ?>
            <?= $this->Html->link(
                __d('work_reports', 'Previous Month'),
                $monthUrl($month->subMonths(1)),
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __d('work_reports', 'Next Month'),
                $monthUrl($month->addMonths(1)),
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('work_reports', 'List Work Reports'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
        <div class="row">
            <div class="column">
                <?= $this->Form->control('user_id', [
                    'label' => __d('work_reports', 'User'),
                    'options' => $workers,
                    'value' => $workReport->user_id,
                    'onchange' => $this::SUBMIT_ON_CHANGE,
                ]) ?>
            </div>
            <div class="column">
                <?= $this->Form->control('month', [
                    'label' => __d('work_reports', 'Month'),
                    'type' => 'month',
                    'value' => $month->format('Y-m'),
                    'onchange' => $this::SUBMIT_ON_CHANGE,
                ]) ?>
            </div>
        </div>
        <?= $this->Form->end() ?>

        <div class="work-reports view content">
            <?= $this->record(
                __d('work_reports', 'Work Report'),
                (string)$month->i18nFormat('LLLL yyyy'),
                $workerName,
            ) ?>
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
                            <th><?= __d('work_reports', 'State') ?></th>
                            <td>
                                <?= h($workReport->state->label()) ?>
                                <?php if ($workReport->submitted !== null) : ?>
                                    (<?= h($workReport->submitted) ?>)
                                <?php endif ?>
                            </td>
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
            <div class="text">
                <strong><?= __d('work_reports', 'Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($workReport->note)); ?>
                </blockquote>
            </div>

            <div class="related">
                <?php if (!$workReport->isLocked()) : ?>
                    <?= $this->AuthLink->link(
                        __d('work_reports', 'New Work Report Item'),
                        $addUrl($month),
                        ['class' => 'button button-small float-right win-link'],
                    ) ?>
                <?php endif ?>
                <h4 id="work-report-items"><?= __d('work_reports', 'Work Report Items') ?></h4>
                <?php if ($summary->missingDays !== []) : ?>
                    <div class="message warning" role="alert">
                        <?= __d(
                            'work_reports',
                            'Nothing is reported on {0}.',
                            implode(', ', array_map(
                                fn(Date $day): string => (string)$day->i18nFormat('d. M.'),
                                $summary->missingDays,
                            )),
                        ) ?>
                    </div>
                <?php endif ?>
                <div class="table-responsive">
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
                            <th><?= __d('work_reports', 'Work Labels') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($days as $day) : ?>
                            <?php
                            $date = $day['date'];
                            if ($summary->isMissing($date)) {
                                $style = 'background-color: var(--color-message-warning-bg);';
                            } elseif (!$calendar->isWorkingDay($date)) {
                                $style = 'background-color: var(--color-card-bg);';
                            } else {
                                $style = '';
                            }
                            $rows = $day['items'] ?: [null];
                            ?>
                            <?php foreach ($rows as $index => $item) : ?>
                            <tr style="<?= $style ?>">
                                <td>
                                    <?php if ($index === 0) : ?>
                                        <?= h($date->i18nFormat('EEE d. M.')) ?>
                                        <?php if ($calendar->isHoliday($date)) : ?>
                                            <br><small><?= __d('work_reports', 'public holiday') ?></small>
                                        <?php endif ?>
                                    <?php endif ?>
                                </td>
                                <?php if ($item === null) : ?>
                                    <td colspan="8"></td>
                                <?php else : ?>
                                    <?php if ($item->whole_day) : ?>
                                        <td colspan="3"><?= __d('work_reports', 'whole day') ?></td>
                                    <?php else : ?>
                                        <td><?= h($item->time_from) ?></td>
                                        <td><?= h($item->time_until) ?></td>
                                        <td><?= $minutes($item->minutes) ?></td>
                                    <?php endif ?>
                                    <td><?= h($item->work_report_item_type->name) ?></td>
                                    <td>
                                        <?= nl2br(h($item->description)) ?>
                                        <?php if ($item->billable) : ?>
                                            <br><small>
                                                <?= __d('work_reports', 'To bill') ?>:
                                                <?= $this->Number->format($item->billed_hours?->toFloat() ?? 0) ?> h
                                                <?= h($item->work_rate?->code) ?>
                                                <?= $item->rate_multiplier->toFloat() != 1
                                                    ? '× ' . $this->Number->format($item->rate_multiplier->toFloat())
                                                    : '' ?>
                                                <?= $item->charged ? '✓' : '' ?>
                                            </small>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?= $item->customer === null ? '' : $this->Html->link(
                                            (string)$item->customer->name,
                                            [
                                                'plugin' => null,
                                                'controller' => 'Customers',
                                                'action' => 'view',
                                                $item->customer->id,
                                            ],
                                        ) ?>
                                        <?php if ($item->contract !== null) : ?>
                                            <br>
                                            <?= $this->Html->link(
                                                (string)$item->contract->number,
                                                [
                                                    'plugin' => null,
                                                    'controller' => 'Contracts',
                                                    'action' => 'view',
                                                    $item->contract->id,
                                                    'customer_id' => $item->customer_id,
                                                ],
                                            ) ?>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php if ($item->private_car_distance) : ?>
                                            <?= $this->Number->format($item->private_car_distance) ?> km
                                            <?= h($item->private_car?->name) ?>
                                        <?php endif ?>
                                        <?php if ($item->company_car_distance) : ?>
                                            <?= $this->Number->format($item->company_car_distance) ?> km
                                            <?= h($item->company_car?->name) ?>
                                        <?php endif ?>
                                    </td>
                                    <td>
                                        <?php foreach ($item->work_labels as $label) : ?>
                                            <span
                                                class="app-label"
                                                style="<?= $label->style ?>"
                                                title="<?= h($label->caption) ?>"
                                            ><?= h($label->name) ?></span>
                                        <?php endforeach ?>
                                    </td>
                                <?php endif ?>
                                <td class="actions">
                                    <?php if (!$workReport->isLocked()) : ?>
                                        <?php if ($item !== null) : ?>
                                            <?= $this->AuthLink->link(
                                                __('Edit'),
                                                ['controller' => 'WorkReportItems', 'action' => 'edit', $item->id],
                                                ['class' => 'win-link'],
                                            ) ?>
                                            <?= $this->AuthLink->postLink(
                                                __('Delete'),
                                                ['controller' => 'WorkReportItems', 'action' => 'delete', $item->id],
                                                ['confirm' => __('Are you sure you want to delete # {0}?', $item->id)],
                                            ) ?>
                                        <?php endif ?>
                                        <?php if ($index === count($rows) - 1) : ?>
                                            <?= $this->AuthLink->link(
                                                __d('work_reports', 'Add'),
                                                $addUrl($date),
                                                ['class' => 'win-link'],
                                            ) ?>
                                        <?php endif ?>
                                    <?php endif ?>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        <?php endforeach ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
