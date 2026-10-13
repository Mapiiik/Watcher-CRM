<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 * @var int $recoveryCodesLeft
 */

use Cake\Core\Configure;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_users', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('app_users', 'Change Password'),
                ['action' => 'changePassword'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('app_users', 'User Settings'),
                ['action' => 'userSettings'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <?= $this->record(__d('app_users', 'User Profile'), $user->name) ?>
            <table>
                <tr>
                    <th><?= __d('app_users', 'Username') ?></th>
                    <td><?= h($user->username) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_users', 'First Name') ?></th>
                    <td><?= h($user->first_name) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_users', 'Last Name') ?></th>
                    <td><?= h($user->last_name) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_users', 'Email') ?></th>
                    <td><?= h($user->email) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_users', 'Role') ?></th>
                    <td><?= h($user->getRoleName()) ?></td>
                </tr>
                <tr>
                    <th><?= __d('app_users', 'Last Login') ?></th>
                    <td><?= h($user->last_login) ?></td>
                </tr>
            </table>
            <div class="related">
                <?= $this->element('Access.one_time_password', compact('user', 'recoveryCodesLeft')) ?>
            </div>
            <?php if (Configure::read('Users.Social.login')) : ?>
                <div class="related">
                    <h4><?= __d('app_users', 'Social Accounts') ?></h4>
                    <?php if (!empty($user->social_accounts)) : ?>
                        <table>
                            <tr>
                                <th><?= __d('app_users', 'Provider') ?></th>
                                <th><?= __d('app_users', 'Username') ?></th>
                            </tr>
                            <?php foreach ($user->social_accounts as $socialAccount) : ?>
                                <tr>
                                    <td><?= h($socialAccount->provider) ?></td>
                                    <td><?= h($socialAccount->username) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                    <?= $this->User->socialConnectLinkList($user->social_accounts ?? []) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
