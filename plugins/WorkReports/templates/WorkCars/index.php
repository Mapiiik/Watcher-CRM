<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\WorkReports\Model\Entity\WorkCar> $records
 */
?>
<div class="work-cars index content">
    <?= $this->AuthLink->link(
        __d('work_reports', 'New Work Car'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work Cars')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name', __d('work_reports', 'Name')) ?></th>
                    <th><?= $this->Paginator->sort('license_plate', __d('work_reports', 'License Plate')) ?></th>
                    <th><?= $this->Paginator->sort('owner_id', __d('work_reports', 'Owner')) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('work_reports', 'Active')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                <tr>
                    <td><?= h($record->name) ?></td>
                    <td><?= h($record->license_plate) ?></td>
                    <td>
                        <?= $record->owner === null ? __d('work_reports', 'company car') : h($record->owner->name) ?>
                    </td>
                    <td><?= $record->active ? __('Yes') : __('No') ?></td>
                    <td class="actions">
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
