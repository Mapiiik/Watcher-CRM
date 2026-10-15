<?php
/**
 * @var \App\View\AppView $this
 * @var \Access\Model\Entity\User $user
 * @var int $recoveryCodesLeft
 */

use Access\Social\Providers;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('access', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('access', 'Edit User'),
                ['action' => 'edit', $user->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Form->postLink(
                __d('access', 'Delete User'),
                ['action' => 'delete', $user->id],
                [
                    'confirm' => __d('access', 'Are you sure you want to delete # {0}?', $user->id),
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
            <?= $this->Html->link(
                __d('access', 'List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->Html->link(
                __d('access', 'New User'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <?= $this->record(__d('access', 'User'), $user->name) ?>
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
                        <tr>
                            <th><?= __d('access', 'API Key') ?></th>
                            <td><?= $user->api_token ? __d('access', 'Set') : __d('access', 'None') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <?= $this->element('Access.two_step_verification', [
                'user' => $user,
                'recoveryCodesLeft' => $recoveryCodesLeft,
                'own' => false,
            ]) ?>
            <?php if (!empty($user->social_accounts) || Providers::names() !== []) : ?>
                <div class="related">
                    <h4><?= __d('access', 'Social Accounts') ?></h4>
                    <?php if (!empty($user->social_accounts)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __d('access', 'Provider') ?></th>
                                    <th><?= __d('access', 'Username') ?></th>
                                    <th><?= __d('access', 'Reference') ?></th>
                                    <th><?= __d('access', 'Active') ?></th>
                                    <th><?= __d('access', 'Created') ?></th>
                                </tr>
                                <?php foreach ($user->social_accounts as $link) : ?>
                                    <tr>
                                        <td><?= h(Providers::label($link->provider)) ?></td>
                                        <td><?= h($link->username) ?></td>
                                        <td><?= h($link->reference) ?></td>
                                        <td><?= $link->active ? __d('access', 'Yes') : __d('access', 'No') ?></td>
                                        <td><?= h($link->created) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
