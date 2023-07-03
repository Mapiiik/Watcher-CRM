<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\Cake\Datasource\EntityInterface> $radgroupreplies
 */
?>
<div class="radgroupreply index content">
    <?= $this->AuthLink->link(
        __d('radius', 'New RADIUS Group Reply'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'RADIUS Group Replies') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('groupname') ?></th>
                    <th><?= $this->Paginator->sort('attribute') ?></th>
                    <th><?= $this->Paginator->sort('op') ?></th>
                    <th><?= $this->Paginator->sort('value') ?></th>
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radgroupreplies as $radgroupreply) : ?>
                <tr>
                    <td><?= h($radgroupreply->groupname) ?></td>
                    <td><?= h($radgroupreply->attribute) ?></td>
                    <td><?= h($radgroupreply->op) ?></td>
                    <td><?= h($radgroupreply->value) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__d('radius', 'View'), ['action' => 'view', $radgroupreply->id]) ?>
                        <?= $this->AuthLink->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $radgroupreply->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $radgroupreply->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radgroupreply->id)]
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
