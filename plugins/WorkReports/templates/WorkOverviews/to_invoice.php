<?php
use WorkReports\Controller\WorkOverviewsController;
use WorkReports\Service\WorkReportSummary;

/**
 * @var \App\View\AppView $this
 * @var array<string, array{customer: \App\Model\Entity\Customer|null, items: list<\WorkReports\Model\Entity\WorkReportItem>, amount: \PhpCollective\DecimalObject\Decimal}> $groups
 * @var \PhpCollective\DecimalObject\Decimal $total
 * @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer> $customers
 * @var string $invoiced
 * @var \Cake\I18n\Date|null $from
 * @var \Cake\I18n\Date|null $to
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('invoiced', [
            'label' => __d('work_reports', 'Invoiced'),
            'options' => ['0' => __('No'), '1' => __('Yes')],
            'empty' => __d('work_reports', 'All'),
            'value' => $invoiced,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('customer_id', [
            'label' => __d('work_reports', 'Customer'),
            'options' => $customers,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('from', [
            'type' => 'date',
            'label' => __('From'),
            'value' => $from?->toDateString(),
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('to', [
            'type' => 'date',
            'label' => __('To'),
            'value' => $to?->toDateString(),
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="overviews index content">
    <?= $this->AuthLink->link(
        __('List Overviews'),
        ['plugin' => null, 'controller' => 'Overviews', 'action' => 'index'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work to Invoice')) ?>
    <div>
        <?= __d('work_reports', 'Items') ?>:
        <?= $this->Number->format(array_sum(array_map(fn($group): int => count($group['items']), $groups))) ?>
        (<?= $this->Number->currency($total->toFloat()) ?>)
        <br>
        <small class="hint">
            <?= __d('work_reports', 'Items with a rate without a price add nothing to the amount.') ?>
        </small>
    </div>

    <?= $this->Form->create(null, ['url' => ['action' => 'markInvoiced']]) ?>
    <?php $this->Form->unlockField('ids'); ?>
    <?php $this->Form->unlockField('invoiced'); ?>
    <?php foreach ($groups as $group) : ?>
    <div class="related">
        <h4>
            <?php if ($group['customer'] === null) : ?>
                <?= __d('work_reports', 'Without a customer') ?>
            <?php else : ?>
                <?= $this->Html->link(
                    (string)$group['customer']->name,
                    ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $group['customer']->id],
                ) ?>
            <?php endif ?>
            - <?= $this->Number->currency($group['amount']->toFloat()) ?>
        </h4>
        <div class="table-responsive">
            <table>
                <tr>
                    <th></th>
                    <th><?= __d('work_reports', 'Date') ?></th>
                    <th><?= __d('work_reports', 'User') ?></th>
                    <th><?= __d('work_reports', 'Contract') ?></th>
                    <th><?= __d('work_reports', 'Description') ?></th>
                    <th><?= __d('work_reports', 'Invoice Text') ?></th>
                    <th><?= __d('work_reports', 'Hours') ?></th>
                    <th><?= __d('work_reports', 'Invoice Hours') ?></th>
                    <th><?= __d('work_reports', 'Work Rate') ?></th>
                    <th><?= __d('work_reports', 'Rate Multiplier') ?></th>
                    <th><?= __d('work_reports', 'Amount') ?></th>
                    <th><?= __d('work_reports', 'Invoiced') ?></th>
                </tr>
                <?php foreach ($group['items'] as $item) : ?>
                    <?php $amount = WorkOverviewsController::amountOf($item); ?>
                <tr>
                    <td>
                        <input type="checkbox" name="ids[]" value="<?= h($item->id) ?>">
                    </td>
                    <td><?= h($item->date) ?></td>
                    <td><?= h($item->work_report->user->name) ?></td>
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
                    <td><?= nl2br(h((string)$item->description)) ?></td>
                    <td><?= nl2br(h((string)$item->invoice_text)) ?></td>
                    <td><?= $item->whole_day ? '' : WorkReportSummary::formatMinutes($item->minutes) ?></td>
                    <td><?= $this->Number->format($item->invoice_hours?->toFloat() ?? 0) ?></td>
                    <td><?= h($item->work_rate?->name_for_lists) ?></td>
                    <td><?= $this->Number->format($item->rate_multiplier->toFloat()) ?></td>
                    <td><?= $amount === null ? '' : $this->Number->currency($amount->toFloat()) ?></td>
                    <td style="<?= $item->invoice_style ?>"><?= $item->invoiced ? __('Yes') : __('No') ?></td>
                </tr>
                <?php endforeach ?>
            </table>
        </div>
    </div>
    <?php endforeach ?>
    <?php if ($groups !== []) : ?>
        <?= $this->Form->button(__d('work_reports', 'Mark as Invoiced'), ['name' => 'invoiced', 'value' => '1']) ?>
        <?= $this->Form->button(
            __d('work_reports', 'Mark as Not Invoiced'),
            ['name' => 'invoiced', 'value' => '0', 'class' => 'button-outline'],
        ) ?>
    <?php endif ?>
    <?= $this->Form->end() ?>
</div>
