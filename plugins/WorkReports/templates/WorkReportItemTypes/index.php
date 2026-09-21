<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\WorkReports\Model\Entity\WorkReportItemType> $records
 */
?>
<div class="work-report-item-types index content">
    <?= $this->AuthLink->link(
        __d('work_reports', 'New Work Report Item Type'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work Report Item Types')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name', __d('work_reports', 'Name')) ?></th>
                    <th><?= $this->Paginator->sort('time_mode', __d('work_reports', 'Time Mode')) ?></th>
                    <th><?= $this->Paginator->sort(
                        'description_required',
                        __d('work_reports', 'Description Required'),
                    ) ?></th>
                    <th><?= $this->Paginator->sort('counts_as_worked', __d('work_reports', 'Counts As Worked')) ?></th>
                    <th><?= $this->Paginator->sort('reduces_fund', __d('work_reports', 'Reduces Fund')) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('work_reports', 'Active')) ?></th>
                    <th><?= $this->Paginator->sort('position', __d('work_reports', 'Position')) ?></th>
                    <th class="actions"><?= __d('work_reports', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                <tr>
                    <td><?= h($record->name) ?></td>
                    <td><?= h($record->time_mode->label()) ?></td>
                    <td>
                        <?= $record->description_required ? __d('work_reports', 'Yes') : __d('work_reports', 'No') ?>
                    </td>
                    <td><?= $record->counts_as_worked ? __d('work_reports', 'Yes') : __d('work_reports', 'No') ?></td>
                    <td><?= $record->reduces_fund ? __d('work_reports', 'Yes') : __d('work_reports', 'No') ?></td>
                    <td><?= $record->active ? __d('work_reports', 'Yes') : __d('work_reports', 'No') ?></td>
                    <td><?= $this->Number->format($record->position) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('work_reports', 'Edit'),
                            ['action' => 'edit', $record->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('work_reports', 'Delete'),
                            ['action' => 'delete', $record->id],
                            ['confirm' => __d('work_reports', 'Are you sure you want to delete # {0}?', $record->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
