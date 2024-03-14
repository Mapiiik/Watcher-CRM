<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Login $login
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
    <div class="column column-90">
        <div class="logins view content">
            <h3><?= h($login->login) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $login->__isset('customer') ? $this->Html->link(
                                $login->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $login->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $login->__isset('customer') ? h($login->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Login') ?></th>
                            <td><?= h($login->login) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Rights') ?></th>
                            <td><?= h($login->rights->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Locked') ?></th>
                            <td><?= $login->locked ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                    <table>
                        <tr>
                            <th><?= __('Last Granted') ?></th>
                            <td><?= h($login->last_granted) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Granted IP Address') ?></th>
                            <td><?= h($login->last_granted_ip) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Denied') ?></th>
                            <td><?= h($login->last_denied) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Last Denied IP Address') ?></th>
                            <td><?= h($login->last_denied_ip) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($login->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($login->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $login->__isset('creator') ? $this->Html->link(
                                $login->creator->username,
                                [
                                    'controller' => 'AppUsers',
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
                            <td><?= $login->__isset('modifier') ? $this->Html->link(
                                $login->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $login->modifier->id,
                                ]
                            ) : h($login->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
