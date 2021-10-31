<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskType[]|\Cake\Collection\CollectionInterface $taskTypes
 */
?>
<div class="taskTypes index content">
    <?= $this->AuthLink->link(__('New Task Type'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Task Types') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($taskTypes as $taskType): ?>
                <tr>
                    <td><?= $this->Number->format($taskType->id) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $taskType->id]) ?>
                        <?= $this->AuthLink->link(__('Edit'), ['action' => 'edit', $taskType->id]) ?>
                        <?= $this->AuthLink->postLink(__('Delete'), ['action' => 'delete', $taskType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $taskType->id)]) ?>
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
