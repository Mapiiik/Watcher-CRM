<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Access\Model\Entity\User> $users
 */
?>
<div class="users index content">
    <?= $this->Html->link(
        __d('access', 'New User'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->heading(__d('access', 'Users')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('email') ?></th>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <?= $this->element('Access.account_fields', ['part' => 'heading']) ?>
                    <th><?= $this->Paginator->sort('is_superuser') ?></th>
                    <th><?= $this->Paginator->sort('role') ?></th>
                    <th><?= $this->Paginator->sort('secret_verified', __d('access', 'Two-Step Verification')) ?></th>
                    <th><?= $this->Paginator->sort('last_login') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th class="actions"><?= __d('access', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?= h($user->username) ?></td>
                    <td><?= h($user->email) ?></td>
                    <td><?= h($user->first_name) ?></td>
                    <td><?= h($user->last_name) ?></td>
                    <td><?= $user->active ? __d('access', 'Yes') : __d('access', 'No'); ?></td>
                    <?= $this->element('Access.account_fields', ['part' => 'cell', 'user' => $user]) ?>
                    <td><?= $user->is_superuser ? __d('access', 'Yes') : __d('access', 'No'); ?></td>
                    <td><?= h($user->getRoleName()) ?></td>
                    <td><?= $user->hasTwoStepVerification() ? __d('access', 'On') : __d('access', 'Off') ?></td>
                    <td><?= h($user->last_login) ?></td>
                    <td><?= h($user->created) ?></td>
                    <td><?= h($user->modified) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(
                            __d('access', 'View'),
                            ['action' => 'view', $user->id],
                        ) ?>
                        <?= $this->Html->link(
                            __d('access', 'Edit'),
                            ['action' => 'edit', $user->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('access', 'Delete'),
                            ['action' => 'delete', $user->id],
                            ['confirm' => __d('access', 'Are you sure you want to delete # {0}?', $user->id)],
                        ) ?>
                        <?= $this->Html->link(
                            __d('access', 'Change Password'),
                            ['action' => 'changePassword', $user->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->Html->link(
                            __d('access', 'User Settings'),
                            ['action' => 'userSettings', $user->id],
                            ['class' => 'win-link'],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
