<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Radius\Model\Entity\Account> $accounts
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __d('radius', 'Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="accounts index content">
    <?= $this->AuthLink->link(
        __d('radius', 'Settings'),
        ['controller' => 'Settings', 'action' => 'index'],
        ['class' => 'button float-right']
    ) ?>
    <?= $this->AuthLink->link(
        __d('radius', 'New RADIUS Account'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('radius', 'RADIUS Accounts') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id', __d('radius', 'Customer')) ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __d('radius', 'Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id', __d('radius', 'Contract')) ?></th>
                    <th><?= $this->Paginator->sort('username', __d('radius', 'Username')) ?></th>
                    <th><?= $this->Paginator->sort('password', __d('radius', 'Password')) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('radius', 'Active')) ?></th>
                    <th><?= $this->Paginator->sort('type', __d('radius', 'Type')) ?></th>
                    <th><?= $this->Paginator->sort('created', __d('radius', 'Created')) ?></th>
                    <th><?= $this->Paginator->sort('modified', __d('radius', 'Modified')) ?></th>
                    <th class="actions"><?= __d('radius', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($accounts as $account) : ?>
                <tr>
                    <td>
                        <?= $account->__isset('customer') ? $this->Html->link(
                            $account->customer->name,
                            ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $account->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $account->__isset('customer') ? h($account->customer->number) : '' ?></td>
                    <td>
                        <?= $account->__isset('contract') ? $this->Html->link(
                            $account->contract->number ?? '--',
                            [
                                'plugin' => null,
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $account->contract->id,
                                'customer_id' => $account->contract->customer_id,
                            ]
                        ) : '' ?>
                    </td>
                    <td><?= h($account->username) ?></td>
                    <td><?= h($account->password) ?></td>
                    <td><?= $account->active ? __d('radius', 'Yes') : __d('radius', 'No'); ?></td>
                    <td><?= h($account->type->label()) ?></td>
                    <td><?= h($account->created) ?></td>
                    <td><?= h($account->modified) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('radius', 'Monitoring'),
                            ['action' => 'monitoring', $account->id]
                        ) ?>
                        <?= $this->AuthLink->link(__d('radius', 'View'), ['action' => 'view', $account->id]) ?>
                        <?= $this->AuthLink->link(
                            __d('radius', 'Edit'),
                            ['action' => 'edit', $account->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
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
