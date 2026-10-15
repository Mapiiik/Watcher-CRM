<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var int $recoveryCodesLeft
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('access', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('access', 'Change Password'),
                ['action' => 'changePassword'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('access', 'User Settings'),
                ['action' => 'userSettings'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <?= $this->record(__d('access', 'User Profile'), $user->name) ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('access', 'Username') ?></th>
                            <td><?= h($user->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'First Name') ?></th>
                            <td><?= h($user->first_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'Last Name') ?></th>
                            <td><?= h($user->last_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'Email') ?></th>
                            <td><?= h($user->email) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'Role') ?></th>
                            <td><?= h($user->getRoleName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'Is Superuser') ?></th>
                            <td><?= $user->is_superuser ? __d('access', 'Yes') : __d('access', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'Active') ?></th>
                            <td><?= $user->active ? __d('access', 'Yes') : __d('access', 'No'); ?></td>
                        </tr>
                        <?= $this->element('Access.account_fields', ['part' => 'details', 'user' => $user]) ?>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <?= $this->element('Access.sign_ins', ['user' => $user]) ?>
                        <tr>
                            <th><?= __d('access', 'Created') ?></th>
                            <td><?= h($user->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'Modified') ?></th>
                            <td><?= h($user->modified) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?= $this->element('Access.two_step_verification', [
                'user' => $user,
                'recoveryCodesLeft' => $recoveryCodesLeft,
                'own' => true,
            ]) ?>
            <?= $this->element('Access.social_accounts', compact('user')) ?>
        </div>
    </div>
</div>
