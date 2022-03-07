<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $accounts
 */
?>
<div class="accounts index content">
    <?= $this->Html->link(
        __d('radius', 'New Account'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'Accounts') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('password') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account) : ?>
                <tr>
                    <td><?= $this->Number->format($account->id) ?></td>
                    <td>
                        <?= $account->has('customer') ? $this->Html->link(
                            $account->customer->name,
                            ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $account->customer->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $account->has('contract') ? $this->Html->link(
                            $account->contract->number,
                            [
                                'plugin' => null,
                                'controller' => 'Contracts',
                                'action' => 'view',
                                'customer_id' => $account->contract->customer_id,
                                $account->contract->id,
                            ]
                        ) : '' ?>
                    </td>
                    <td><?= h($account->username) ?></td>
                    <td><?= h($account->password) ?></td>
                    <td><?= $account->active ? __d('radius', 'Yes') : __d('radius', 'No'); ?></td>
                    <td><?= $this->Number->format($account->type) ?></td>
                    <td><?= h($account->created) ?></td>
                    <td><?= $this->Number->format($account->created_by) ?></td>
                    <td><?= h($account->modified) ?></td>
                    <td><?= $this->Number->format($account->modified_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__d('radius', 'View'), ['action' => 'view', $account->id]) ?>
                        <?= $this->Html->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $account->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('radius', 'Delete'),
                            ['action' => 'delete', $account->id],
                            ['confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->id)]
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
