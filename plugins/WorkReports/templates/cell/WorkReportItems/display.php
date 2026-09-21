<?php
use WorkReports\Service\WorkReportSummary;

/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\ResultSetInterface<int, \WorkReports\Model\Entity\WorkReportItem> $items
 * @var bool $show_contracts
 */
?>
<?php if (!$items->isEmpty()) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __d('work_reports', 'Date') ?></th>
            <th><?= __d('work_reports', 'User') ?></th>
            <th><?= __d('work_reports', 'Hours') ?></th>
            <th><?= __d('work_reports', 'Work Report Item Type') ?></th>
            <th><?= __d('work_reports', 'Description') ?></th>
            <?php if ($show_contracts) : ?>
            <th><?= __d('work_reports', 'Contract') ?></th>
            <?php endif ?>
            <th><?= __d('work_reports', 'To Invoice') ?></th>
            <th><?= __d('work_reports', 'Work Labels') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($items as $item) : ?>
        <tr>
            <td><?= h($item->date) ?></td>
            <td><?= h($item->work_report->user->name) ?></td>
            <td>
                <?php if ($item->whole_day) : ?>
                    <?= __d('work_reports', 'whole day') ?>
                <?php else : ?>
                    <?= h($item->time_from) ?>–<?= h($item->time_until) ?>
                    (<?= WorkReportSummary::formatMinutes($item->minutes) ?>)
                <?php endif ?>
            </td>
            <td><?= h($item->work_report_item_type->name) ?></td>
            <td><?= nl2br(h((string)$item->description)) ?></td>
            <?php if ($show_contracts) : ?>
            <td>
                <?= $item->contract === null ? '' : $this->Html->link(
                    (string)$item->contract->number,
                    [
                        'plugin' => null,
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $item->contract->id,
                        'customer_id' => $item->customer_id,
                    ],
                ) ?>
            </td>
            <?php endif ?>
            <td style="<?= $item->invoice_style ?>">
                <?php if ($item->to_invoice) : ?>
                    <?= $this->Number->format($item->invoice_hours?->toFloat() ?? 0) ?> h
                    <?= h($item->work_rate?->code) ?>
                    <?= $item->rate_multiplier->toFloat() != 1
                        ? '× ' . $this->Number->format($item->rate_multiplier->toFloat())
                        : '' ?>
                    <?= $item->invoiced ? '✓' : '' ?>
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
            <td class="actions">
                <?= $this->AuthLink->link(
                    __d('work_reports', 'Work Report'),
                    [
                        'plugin' => 'WorkReports',
                        'controller' => 'WorkReports',
                        'action' => 'sheet',
                        'customer_id' => null,
                        'contract_id' => null,
                        '?' => [
                            'user_id' => $item->work_report->user_id,
                            'month' => $item->date->format('Y-m'),
                        ],
                    ],
                ) ?>
                <?php if (!$item->work_report->isLocked()) : ?>
                    <?= $this->AuthLink->link(
                        __('Edit'),
                        ['plugin' => 'WorkReports', 'controller' => 'WorkReportItems', 'action' => 'edit', $item->id],
                        ['class' => 'win-link'],
                    ) ?>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </table>
</div>
<?php endif ?>
