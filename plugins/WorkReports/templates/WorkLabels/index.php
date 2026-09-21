<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\WorkReports\Model\Entity\WorkLabel> $records
 */
?>
<div class="work-labels index content">
    <?= $this->AuthLink->link(
        __d('work_reports', 'New Work Label'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work Labels')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name', __d('work_reports', 'Name')) ?></th>
                    <th><?= $this->Paginator->sort('caption', __d('work_reports', 'Caption')) ?></th>
                    <th><?= $this->Paginator->sort('color', __d('work_reports', 'Color')) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('work_reports', 'Active')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record) : ?>
                <tr>
                    <td><?= h($record->name) ?></td>
                    <td><?= h($record->caption) ?></td>
                    <td style="<?= $record->style ?>"><?= h($record->color) ?></td>
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
