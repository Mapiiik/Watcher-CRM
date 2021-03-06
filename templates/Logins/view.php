<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Login $login
 * @var string[]|\Cake\Collection\CollectionInterface $rights
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Login'),
                ['action' => 'edit', $login->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Login'),
                ['action' => 'delete', $login->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $login->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Logins'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Login'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="logins view content">
            <h3><?= h($login->login) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $login->has('customer') ? $this->Html->link(
                        $login->customer->name,
                        ['controller' => 'Customers', 'action' => 'view', $login->customer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Login') ?></th>
                    <td><?= h($login->login) ?></td>
                </tr>
                <tr>
                    <th><?= __('Rights') ?></th>
                    <td><?= $rights[$login->rights] ?></td>
                </tr>
                <tr>
                    <th><?= __('Locked') ?></th>
                    <td><?= $login->locked ? __('Yes') : __('No'); ?></td>
                </tr>
                <tr>
                    <th><?= __('Last Granted') ?></th>
                    <td><?= h($login->last_granted) ?></td>
                </tr>
                <tr>
                    <th><?= __('Last Granted Ip') ?></th>
                    <td><?= h($login->last_granted_ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Last Denied') ?></th>
                    <td><?= h($login->last_denied) ?></td>
                </tr>
                <tr>
                    <th><?= __('Last Denied Ip') ?></th>
                    <td><?= h($login->last_denied_ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($login->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($login->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $login->has('creator') ? $this->Html->link(
                        $login->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $login->creator->id,
                        ]
                    ) : h($login->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($login->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $login->has('modifier') ? $this->Html->link(
                        $login->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $login->modifier->id,
                        ]
                    ) : h($login->modified_by) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
