<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\TaskState> $taskStates
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="taskStates index content">
    <?= $this->AuthLink->link(__('New Task State'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Task States') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('name') ?></th>
                    <th><?= $this->Paginator->sort('color') ?></th>
                    <th><?= $this->Paginator->sort('priority') ?></th>
                    <th><?= $this->Paginator->sort('completed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taskStates as $taskState) : ?>
                <tr>
                    <td><?= h($taskState->name) ?></td>
                    <td style="background-color: <?= h($taskState->color) ?>;"><?= h($taskState->color) ?></td>
                    <td><?= h($taskState->priority) ?></td>
                    <td><?= $taskState->completed ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $taskState->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $taskState->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $taskState->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $taskState->id)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
