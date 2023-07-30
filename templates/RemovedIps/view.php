<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIp $removedIp
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Removed IP Address'),
                ['action' => 'edit', $removedIp->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Removed IP Address'),
                ['action' => 'delete', $removedIp->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List Removed IP Addresses'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Removed IP Address'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="removedIps view content">
            <h3><?= h($removedIp->ip) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $removedIp->__isset('customer') ? $this->Html->link(
                                $removedIp->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $removedIp->__isset('customer') ? h($removedIp->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $removedIp->__isset('contract') ? $this->Html->link(
                                $removedIp->contract->number ?? '--',
                                ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address') ?></th>
                            <td><?= h($removedIp->ip) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($removedIp->getTypeOfUseName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($removedIp->ip_address_ranges)) {
                                $range = $removedIp->ip_address_ranges->first();
                                echo isset($range['access_point']['id']) ?
                                    __('Access Point') . ': ' . $this->Html->link(
                                        $range['access_point']['name'],
                                        env('WATCHER_NMS_URL') . '/access-points/view/' . $range['access_point']['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                echo isset($range['id']) ?
                                    __('Range') . ': ' . $this->Html->link(
                                        $range['name'],
                                        env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                    unset($range);
                            }
                            ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($removedIp->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($removedIp->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $removedIp->__isset('creator') ? $this->Html->link(
                                $removedIp->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIp->creator->id,
                                ]
                            ) : h($removedIp->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($removedIp->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $removedIp->__isset('modifier') ? $this->Html->link(
                                $removedIp->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIp->modifier->id,
                                ]
                            ) : h($removedIp->modified_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Removed') ?></th>
                            <td><?= h($removedIp->removed) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Removed By') ?></th>
                            <td><?= $removedIp->__isset('remover') ? $this->Html->link(
                                $removedIp->remover->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIp->remover->id,
                                ]
                            ) : h($removedIp->removed_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIp->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
