<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radreplies
 */
?>
<div class="radreply index content">
    <?= $this->Html->link(
        __d('radius', 'New RADIUS Reply'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'RADIUS Replies') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('attribute') ?></th>
                    <th><?= $this->Paginator->sort('op') ?></th>
                    <th><?= $this->Paginator->sort('value') ?></th>
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radreplies as $radreply) : ?>
                <tr>
                    <td>
                        <?= $radreply->has('account') ? $this->Html->link(
                            $radreply->account->username,
                            ['controller' => 'Accounts', 'action' => 'view', $radreply->account->id]
                        ) : $radreply->username ?>
                    </td>
                    <td><?= h($radreply->attribute) ?></td>
                    <td><?= h($radreply->op) ?></td>
                    <td><?= h($radreply->value) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__d('radius', 'View'), ['action' => 'view', $radreply->id]) ?>
                        <?= $this->Html->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $radreply->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $radreply->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radreply->id)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __d('radius', 'first')) ?>
            <?= $this->Paginator->prev('< ' . __d('radius', 'previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__d('radius', 'next') . ' >') ?>
            <?= $this->Paginator->last(__d('radius', 'last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __d('radius', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
