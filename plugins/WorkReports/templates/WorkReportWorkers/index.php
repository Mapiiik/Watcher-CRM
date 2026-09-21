<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\WorkReports\Model\Entity\WorkReportWorker> $records
 */
?>
<div class="work-report-workers index content">
    <?= $this->AuthLink->link(
        __d('work_reports', 'New Work Report Worker'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work Report Workers')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('user_id', __d('work_reports', 'User')) ?></th>
                    <th><?= $this->Paginator->sort('workload', __d('work_reports', 'Workload')) ?></th>
                    <th><?= __d('work_reports', 'Recipients') ?></th>
                    <th><?= $this->Paginator->sort(
                        'default_private_car_id',
                        __d('work_reports', 'Default Private Car'),
                    ) ?></th>
                    <th><?= $this->Paginator->sort(
                        'default_company_car_id',
                        __d('work_reports', 'Default Company Car'),
                    ) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('work_reports', 'Active')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                <tr>
                    <td><?= h($record->user->name) ?></td>
                    <td><?= $this->Number->format($record->workload->toFloat()) ?></td>
                    <td><?= h(implode(', ', array_map(fn($user): string => $user->name, $record->recipients))) ?></td>
                    <td><?= h($record->default_private_car?->name_for_lists) ?></td>
                    <td><?= h($record->default_company_car?->name_for_lists) ?></td>
                    <td><?= $record->active ? __('Yes') : __('No') ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $record->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $record->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $record->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $record->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
