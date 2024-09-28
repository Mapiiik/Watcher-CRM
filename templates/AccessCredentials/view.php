<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AccessCredential $accessCredential
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Access Credential'),
                ['action' => 'edit', $accessCredential->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Access Credential'),
                ['action' => 'delete', $accessCredential->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $accessCredential->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Access Credentials'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Access Credential'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="accessCredentials view content">
            <h3><?= h($accessCredential->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $accessCredential->hasValue('customer') ?
                                $this->Html->link(
                                    $accessCredential->customer->name_for_lists,
                                    ['controller' => 'Customers', 'action' => 'view', $accessCredential->customer->id]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $accessCredential->hasValue('contract') ?
                                $this->Html->link(
                                    $accessCredential->contract->name,
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $accessCredential->contract->id,
                                        'customer_id' => $accessCredential->contract->customer_id,
                                    ]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($accessCredential->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Username') ?></th>
                            <td><?= h($accessCredential->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Password') ?></th>
                            <td><?= h($accessCredential->password) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address') ?></th>
                            <td><?= h($accessCredential->ip_address) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Port') ?></th>
                            <td><?= $accessCredential->port === null ?
                                '' : $this->Number->format($accessCredential->port) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($accessCredential->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($accessCredential->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $accessCredential->__isset('creator') ? $this->Html->link(
                                $accessCredential->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $accessCredential->creator->id,
                                ]
                            ) : h($accessCredential->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($accessCredential->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $accessCredential->__isset('modifier') ? $this->Html->link(
                                $accessCredential->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $accessCredential->modifier->id,
                                ]
                            ) : h($accessCredential->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($accessCredential->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
