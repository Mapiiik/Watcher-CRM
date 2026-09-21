<?php
use WorkReports\Service\WorkReportSummary;

/**
 * @var \App\View\AppView $this
 * @var array<string, array{name: string|null, items: list<\WorkReports\Model\Entity\WorkReportItem>, minutes: int}> $groups
 * @var \App\Http\Answer<array<string, string>> $accessPoints
 * @var array<string, string> $names
 * @var \Cake\I18n\Date|null $from
 * @var \Cake\I18n\Date|null $to
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('access_point_id', [
            'label' => __d('work_reports', 'Access Point'),
            'options' => $names,
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
    <?= $this->heading(__d('work_reports', 'Work at Access Points')) ?>
    <?php foreach ($groups as $accessPointId => $group) : ?>
    <div class="related">
        <h4>
            <?= $this->element('AccessPoints/link', [
                'id' => (string)$accessPointId,
                'name' => $group['name'],
                'answer' => $accessPoints,
            ]) ?>
            - <?= WorkReportSummary::formatMinutes($group['minutes']) ?> h
        </h4>
        <div class="table-responsive">
            <table>
                <tr>
                    <th><?= __d('work_reports', 'Date') ?></th>
                    <th><?= __d('work_reports', 'User') ?></th>
                    <th><?= __d('work_reports', 'Hours') ?></th>
                    <th><?= __d('work_reports', 'Work Report Item Type') ?></th>
                    <th><?= __d('work_reports', 'Description') ?></th>
                    <th><?= __d('work_reports', 'Customer') ?></th>
                </tr>
                <?php foreach ($group['items'] as $item) : ?>
                <tr>
                    <td><?= h($item->date) ?></td>
                    <td><?= h($item->work_report->user->name) ?></td>
                    <td>
                        <?= $item->whole_day
                            ? __d('work_reports', 'whole day')
                            : WorkReportSummary::formatMinutes($item->minutes) ?>
                    </td>
                    <td><?= h($item->work_report_item_type->name) ?></td>
                    <td><?= nl2br(h((string)$item->description)) ?></td>
                    <td>
                        <?= $item->customer === null ? '' : $this->Html->link(
                            (string)$item->customer->name,
                            ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $item->customer->id],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </table>
        </div>
    </div>
    <?php endforeach ?>
</div>
