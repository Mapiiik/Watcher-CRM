<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskState[]|\Cake\Collection\CollectionInterface $taskStates
 */
?>
<div class="taskStates index content">
    <?= $this->Html->link(__('New Task State'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Task States') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taskStates as $taskState): ?>
                <tr>
                    <td><?= $this->Number->format($taskState->id) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $taskState->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $taskState->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $taskState->id], ['confirm' => __('Are you sure you want to delete # {0}?', $taskState->id)]) ?>
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
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
