<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radusergroups
 */
?>
<div class="radusergroup index content">
    <?= $this->Html->link(
        __d('radius', 'New Radusergroup'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'Radusergroup') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('groupname') ?></th>
                    <th><?= $this->Paginator->sort('priority') ?></th>
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radusergroups as $radusergroup) : ?>
                <tr>
                    <td><?= $this->Number->format($radusergroup->id) ?></td>
                    <td><?= h($radusergroup->username) ?></td>
                    <td><?= h($radusergroup->groupname) ?></td>
                    <td><?= $this->Number->format($radusergroup->priority) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__d('radius', 'View'), ['action' => 'view', $radusergroup->id]) ?>
                        <?= $this->Html->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $radusergroup->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $radusergroup->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radusergroup->id)]
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
