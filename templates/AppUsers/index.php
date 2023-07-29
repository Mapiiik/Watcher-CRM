<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AppUser> $users
 * @var string $tableAlias
 */

$users = ${$tableAlias};
?>
<div class="users index content">
    <?= $this->Html->link(
        __d('app_users', 'New User'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __d('app_users', 'Users') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('email') ?></th>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <th><?= $this->Paginator->sort('is_superuser') ?></th>
                    <th><?= $this->Paginator->sort('role') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('last_login') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th class="actions"><?= __d('app_users', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?= h($user->username) ?></td>
                    <td><?= h($user->email) ?></td>
                    <td><?= h($user->first_name) ?></td>
                    <td><?= h($user->last_name) ?></td>
                    <td><?= $user->active ? __d('app_users', 'Yes') : __d('app_users', 'No'); ?></td>
                    <td><?= $user->is_superuser ? __d('app_users', 'Yes') : __d('app_users', 'No'); ?></td>
                    <td><?= h($user->getRoleName()) ?></td>
                    <td><?= h($user->customer_id) ?></td>
                    <td><?= h($user->last_login) ?></td>
                    <td><?= h($user->created) ?></td>
                    <td><?= h($user->modified) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(
                            __d('app_users', 'View'),
                            ['action' => 'view', $user->id]
                        ) ?>
                        <?= $this->Html->link(
                            __d('app_users', 'Edit'),
                            ['action' => 'edit', $user->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('app_users', 'Delete'),
                            ['action' => 'delete', $user->id],
                            ['confirm' => __d('app_users', 'Are you sure you want to delete # {0}?', $user->id)]
                        ) ?>
                        <?= $this->Html->link(
                            __d('app_users', 'Change Password'),
                            ['action' => 'changePassword', $user->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Html->link(
                            __d('app_users', 'User Settings'),
                            ['action' => 'userSettings', $user->id],
                            ['class' => 'win-link']
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __d('app_users', 'first')) ?>
            <?= $this->Paginator->prev('< ' . __d('app_users', 'previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__d('app_users', 'next') . ' >') ?>
            <?= $this->Paginator->last(__d('app_users', 'last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __d('app_users', 'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
