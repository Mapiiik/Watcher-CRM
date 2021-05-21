<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radusergroup
 */
?>
<div class="radusergroup index content">
    <?= $this->Html->link(__('New Radusergroup'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Radusergroup') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('groupname') ?></th>
                    <th><?= $this->Paginator->sort('priority') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radusergroup as $radusergroup): ?>
                <tr>
                    <td><?= h($radusergroup->username) ?></td>
                    <td><?= h($radusergroup->groupname) ?></td>
                    <td><?= $this->Number->format($radusergroup->priority) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $radusergroup->username]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $radusergroup->username]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $radusergroup->username], ['confirm' => __('Are you sure you want to delete # {0}?', $radusergroup->username)]) ?>
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