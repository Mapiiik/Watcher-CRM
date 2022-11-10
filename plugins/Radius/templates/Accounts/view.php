<?php
/**
 * @var \App\View\AppView $this
 * @var \Radius\Model\Entity\Account $account
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('radius', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('radius', 'Edit RADIUS Account'),
                ['action' => 'edit', $account->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Delete RADIUS Account'),
                ['action' => 'delete', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to delete # {0}?', $account->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'List RADIUS Accounts'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __d('radius', 'New RADIUS Account'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="accounts view content">
            <?= $this->Html->link(
                __d('radius', 'RADIUS Account Monitoring'),
                ['action' => 'monitoring', $account->id],
                ['class' => 'button float-right']
            ) ?>
            <?= $this->Form->postLink(
                __d('radius', 'Update Related Records'),
                ['action' => 'updateRelatedRecords', $account->id],
                [
                    'confirm' => __d('radius', 'Are you sure you want to update related records?'),
                    'class' => 'button float-right',
                ]
            ) ?>
            <h3><?= h($account->username) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Customer') ?></th>
                            <td><?= $account->has('customer') ? $this->Html->link(
                                $account->customer->name,
                                [
                                    'plugin' => null,
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $account->customer->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Customer Number') ?></th>
                            <td><?= $account->has('customer') ? h($account->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Contract') ?></th>
                            <td><?= $account->has('contract') ? $this->Html->link(
                                $account->contract->number,
                                [
                                    'plugin' => null,
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    'customer_id' => $account->contract->customer_id,
                                    $account->contract->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Username') ?></th>
                            <td><?= h($account->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Password') ?></th>
                            <td><?= h($account->password) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Type') ?></th>
                            <td><?= h($account->getType()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Active') ?></th>
                            <td><?= $account->active ? __d('radius', 'Yes') : __d('radius', 'No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Id') ?></th>
                            <td><?= $this->Number->format($account->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Created') ?></th>
                            <td><?= h($account->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Created By') ?></th>
                            <td><?= $account->has('creator') ? $this->Html->link(
                                $account->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $account->creator->id,
                                ]
                            ) : h($account->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Modified') ?></th>
                            <td><?= h($account->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('radius', 'Modified By') ?></th>
                            <td><?= $account->has('modifier') ? $this->Html->link(
                                $account->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $account->modifier->id,
                                ]
                            ) : h($account->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <?= $this->Html->link(
                    __d('radius', 'New RADIUS Check'),
                    ['controller' => 'Radcheck', 'action' => 'add', '?' => ['username' => $account->username]],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __d('radius', 'Related RADIUS Checks') ?></h4>
                <?php if (!empty($account->radcheck)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Attribute') ?></th>
                            <th><?= __d('radius', 'Op') ?></th>
                            <th><?= __d('radius', 'Value') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radcheck as $radcheck) : ?>
                        <tr>
                            <td><?= h($radcheck->attribute) ?></td>
                            <td><?= h($radcheck->op) ?></td>
                            <td><?= h($radcheck->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radcheck', 'action' => 'view', $radcheck->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radcheck', 'action' => 'edit', $radcheck->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radcheck', 'action' => 'delete', $radcheck->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radcheck->id
                                    )]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->Html->link(
                    __d('radius', 'New RADIUS Reply'),
                    ['controller' => 'Radreply', 'action' => 'add', '?' => ['username' => $account->username]],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __d('radius', 'Related RADIUS Replies') ?></h4>
                <?php if (!empty($account->radreply)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Attribute') ?></th>
                            <th><?= __d('radius', 'Op') ?></th>
                            <th><?= __d('radius', 'Value') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radreply as $radreply) : ?>
                        <tr>
                            <td><?= h($radreply->attribute) ?></td>
                            <td><?= h($radreply->op) ?></td>
                            <td><?= h($radreply->value) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radreply', 'action' => 'view', $radreply->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radreply', 'action' => 'edit', $radreply->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radreply', 'action' => 'delete', $radreply->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radreply->id
                                    )]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->Html->link(
                    __d('radius', 'New RADIUS User Group'),
                    ['controller' => 'Radusergroup', 'action' => 'add', '?' => ['username' => $account->username]],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __d('radius', 'Related RADIUS User Groups') ?></h4>
                <?php if (!empty($account->radusergroup)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('radius', 'Groupname') ?></th>
                            <th><?= __d('radius', 'Priority') ?></th>
                            <th class="actions"><?= __d('radius', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($account->radusergroup as $radusergroup) : ?>
                        <tr>
                            <td><?= h($radusergroup->groupname) ?></td>
                            <td><?= h($radusergroup->priority) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __d('radius', 'View'),
                                    ['controller' => 'Radusergroup', 'action' => 'view', $radusergroup->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __d('radius', 'Edit'),
                                    ['controller' => 'Radusergroup', 'action' => 'edit', $radusergroup->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('radius', 'Delete'),
                                    ['controller' => 'Radusergroup', 'action' => 'delete', $radusergroup->id],
                                    ['confirm' => __d(
                                        'radius',
                                        'Are you sure you want to delete # {0}?',
                                        $radusergroup->id
                                    )]
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
