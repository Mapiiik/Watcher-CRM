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
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit User'),
                ['action' => 'edit', $user->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete User'),
                ['action' => 'delete', $user->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $user->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List Users'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New User'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="users view content">
            <h3><?= h($user->username) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Username') ?></th>
                            <td><?= h($user->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Email') ?></th>
                            <td><?= h($user->email) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('First Name') ?></th>
                            <td><?= h($user->first_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Name') ?></th>
                            <td><?= h($user->last_name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Role') ?></th>
                            <td><?= h($user->getRoleName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Is Superuser') ?></th>
                            <td><?= $user->is_superuser ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Id') ?></th>
                            <td><?= h($user->customer_id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Active') ?></th>
                            <td><?= $user->active ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Api Token') ?></th>
                            <td><?= h($user->api_token) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Token') ?></th>
                            <td><?= h($user->token) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Token Expires') ?></th>
                            <td><?= h($user->token_expires) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Date') ?></th>
                            <td><?= h($user->activation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Tos Date') ?></th>
                            <td><?= h($user->tos_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Secret') ?></th>
                            <td><?= h($user->secret) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Secret Verified') ?></th>
                            <td><?= $user->secret_verified ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Additional Data') ?></th>
                            <td><?= h($user->additional_data) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Login') ?></th>
                            <td><?= h($user->last_login) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($user->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($user->modified) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Social Accounts') ?></h4>
                <?php if (!empty($user->social_accounts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Provider') ?></th>
                            <th><?= __('Username') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Reference') ?></th>
                            <th><?= __('Avatar') ?></th>
                            <th><?= __('Description') ?></th>
                            <th><?= __('Link') ?></th>
                            <th><?= __('Token') ?></th>
                            <th><?= __('Token Secret') ?></th>
                            <th><?= __('Token Expires') ?></th>
                            <th><?= __('Data') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($user->social_accounts as $socialAccounts) : ?>
                        <tr>
                            <td><?= h($socialAccounts->provider) ?></td>
                            <td><?= h($socialAccounts->username) ?></td>
                            <td><?= h($socialAccounts->active) ?></td>
                            <td><?= h($socialAccounts->reference) ?></td>
                            <td><?= h($socialAccounts->avatar) ?></td>
                            <td><?= h($socialAccounts->description) ?></td>
                            <td><?= h($socialAccounts->link) ?></td>
                            <td><?= h($socialAccounts->token) ?></td>
                            <td><?= h($socialAccounts->token_secret) ?></td>
                            <td><?= h($socialAccounts->token_expires) ?></td>
                            <td><?= h($socialAccounts->data) ?></td>
                            <td><?= h($socialAccounts->created) ?></td>
                            <td><?= h($socialAccounts->modified) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'SocialAccounts', 'action' => 'view', $socialAccounts->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'SocialAccounts', 'action' => 'edit', $socialAccounts->id]
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'SocialAccounts', 'action' => 'delete', $socialAccounts->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $socialAccounts->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
