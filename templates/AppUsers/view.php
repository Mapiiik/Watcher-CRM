<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AppUser $user
 * @var string $tableAlias
 */

$user = ${$tableAlias};
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_users', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('app_users', 'Edit User'),
                ['action' => 'edit', $user->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Form->postLink(
                __d('app_users', 'Delete User'),
                ['action' => 'delete', $user->id],
                [
                    'confirm' => __d('app_users', 'Are you sure you want to delete # {0}?', $user->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->Form->postLink(
                __d('access', 'New API Key'),
                ['action' => 'generateApiToken', $user->id],
                [
                    'confirm' => __d('access', 'The key the account has now will stop working. Make a new one?'),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?php if ($user->api_token) : ?>
                <?= $this->Form->postLink(
                    __d('access', 'Remove API Key'),
                    ['action' => 'removeApiToken', $user->id],
                    [
                        'confirm' => __d('access', 'No client will sign in with the key any more. Remove it?'),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?php if ($user->secret) : ?>
                <?= $this->Form->postLink(
                    __d('access', 'Reset One-Time Codes'),
                    ['action' => 'resetOneTimePasswordAuthenticator', $user->id],
                    [
                        'confirm' => __d(
                            'access',
                            'The account will have to set its authenticator app up again. Reset?',
                        ),
                        'class' => 'side-nav-item',
                    ],
                ) ?>
            <?php endif; ?>
            <?= $this->Html->link(
                __d('app_users', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __d('app_users', 'New User'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <?= $this->record(__d('app_users', 'User'), (string)$user->username) ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('app_users', 'Username') ?></th>
                            <td><?= h($user->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Email') ?></th>
                            <td><?= h($user->email) ?></td>
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
                            <th><?= __d('app_users', 'Role') ?></th>
                            <td><?= h($user->getRoleName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Is Superuser') ?></th>
                            <td><?= $user->is_superuser ? __d('app_users', 'Yes') : __d('app_users', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Customer Id') ?></th>
                            <td><?= h($user->customer_id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Active') ?></th>
                            <td><?= $user->active ? __d('app_users', 'Yes') : __d('app_users', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Holds Tasks') ?></th>
                            <td><?= $user->holds_tasks ? __d('app_users', 'Yes') : __d('app_users', 'No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('access', 'API Key') ?></th>
                            <td><?= $user->api_token ? __d('access', 'Set') : __d('access', 'None') ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Activation Date') ?></th>
                            <td><?= h($user->activation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Tos Date') ?></th>
                            <td><?= h($user->tos_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('access', 'One-Time Codes') ?></th>
                            <td><?= $user->secret_verified ? __d('access', 'On') : __d('access', 'Off') ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Last Login') ?></th>
                            <td><?= h($user->last_login) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Created') ?></th>
                            <td><?= h($user->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_users', 'Modified') ?></th>
                            <td><?= h($user->modified) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __d('app_users', 'Related Social Accounts') ?></h4>
                <?php if (!empty($user->social_accounts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('app_users', 'Provider') ?></th>
                            <th><?= __d('app_users', 'Username') ?></th>
                            <th><?= __d('app_users', 'Reference') ?></th>
                            <th><?= __d('app_users', 'Active') ?></th>
                            <th><?= __d('app_users', 'Created') ?></th>
                        </tr>
                        <?php foreach ($user->social_accounts as $socialAccounts) : ?>
                        <tr>
                            <td><?= h($socialAccounts->provider) ?></td>
                            <td><?= h($socialAccounts->username) ?></td>
                            <td><?= h($socialAccounts->reference) ?></td>
                            <td><?= $socialAccounts->active ? __d('app_users', 'Yes') : __d('app_users', 'No') ?></td>
                            <td><?= h($socialAccounts->created) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
