<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\Cake\Datasource\EntityInterface> $radchecks
 */
?>
<div class="radcheck index content">
    <?= $this->AuthLink->link(
        __d('radius', 'New RADIUS Check'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'RADIUS Checks') ?></h3>
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
                <?php foreach ($radchecks as $radcheck) : ?>
                <tr>
                    <td>
                        <?= $radcheck->has('account') ? $this->Html->link(
                            $radcheck->account->username,
                            ['controller' => 'Accounts', 'action' => 'view', $radcheck->account->id]
                        ) : $radcheck->username ?>
                    </td>
                    <td><?= h($radcheck->attribute) ?></td>
                    <td><?= h($radcheck->op) ?></td>
                    <td><?= h($radcheck->value) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__d('radius', 'View'), ['action' => 'view', $radcheck->id]) ?>
                        <?= $this->AuthLink->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $radcheck->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $radcheck->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $radcheck->id)]
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
