<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radreply
 */
?>
<div class="radreply index content">
    <?= $this->Html->link(__('New Radreply'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Radreply') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('attribute') ?></th>
                    <th><?= $this->Paginator->sort('op') ?></th>
                    <th><?= $this->Paginator->sort('value') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radreply as $radreply): ?>
                <tr>
                    <td><?= $this->Number->format($radreply->id) ?></td>
                    <td><?= $radreply->has('user') ? $this->Html->link($radreply->user->username, ['controller' => 'Users', 'action' => 'view', $radreply->user->id]) : $radreply->username ?></td>
                    <td><?= h($radreply->attribute) ?></td>
                    <td><?= h($radreply->op) ?></td>
                    <td><?= h($radreply->value) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $radreply->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $radreply->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $radreply->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radreply->id)]) ?>
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
