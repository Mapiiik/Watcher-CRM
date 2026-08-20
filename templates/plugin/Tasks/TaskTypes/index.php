<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TaskType> $taskTypes
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __d('app_tasks', 'Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="taskTypes index content">
    <?= $this->AuthLink->link(
        __d('app_tasks', 'New Task Type'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <h3><?= __d('app_tasks', 'Task Types') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('customer_required') ?></th>
                    <th><?= $this->Paginator->sort('contract_required') ?></th>
                    <th class="actions"><?= __d('app_tasks', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taskTypes as $taskType) : ?>
                <tr>
                    <td><?= h($taskType->name) ?></td>
                    <td><?= $taskType->customer_required ? __d('app_tasks', 'Yes') : __d('app_tasks', 'No'); ?></td>
                    <td><?= $taskType->contract_required ? __d('app_tasks', 'Yes') : __d('app_tasks', 'No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__d('app_tasks', 'View'), ['action' => 'view', $taskType->id]) ?>
                        <?= $this->AuthLink->link(
                            __d('app_tasks', 'Edit'),
                            ['action' => 'edit', $taskType->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('app_tasks', 'Delete'),
                            ['action' => 'delete', $taskType->id],
                            ['confirm' => __d('app_tasks', 'Are you sure you want to delete # {0}?', $taskType->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
