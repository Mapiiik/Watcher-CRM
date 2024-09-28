<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpAddress $removedIpAddress
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Removed IP Address'),
                ['action' => 'edit', $removedIpAddress->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Removed IP Address'),
                ['action' => 'delete', $removedIpAddress->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpAddress->id),
                    'class' => 'side-nav-item',
                ]
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
        <div class="removedIpAddresses view content">
            <h3><?= h($removedIpAddress->ip_address) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $removedIpAddress->__isset('customer') ? $this->Html->link(
                                $removedIpAddress->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $removedIpAddress->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $removedIpAddress->__isset('customer') ?
                                h($removedIpAddress->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $removedIpAddress->__isset('contract') ? $this->Html->link(
                                $removedIpAddress->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $removedIpAddress->contract->id,
                                    'customer_id' => $removedIpAddress->contract->customer_id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address') ?></th>
                            <td><?= h($removedIpAddress->ip_address) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($removedIpAddress->type_of_use->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($removedIpAddress->ip_address_ranges)) {
                                $range = $removedIpAddress->ip_address_ranges->first();
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
                            <td><?= h($removedIpAddress->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($removedIpAddress->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $removedIpAddress->__isset('creator') ? $this->Html->link(
                                $removedIpAddress->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIpAddress->creator->id,
                                ]
                            ) : h($removedIpAddress->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($removedIpAddress->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $removedIpAddress->__isset('modifier') ? $this->Html->link(
                                $removedIpAddress->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIpAddress->modifier->id,
                                ]
                            ) : h($removedIpAddress->modified_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Removed') ?></th>
                            <td><?= h($removedIpAddress->removed) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Removed By') ?></th>
                            <td><?= $removedIpAddress->__isset('remover') ? $this->Html->link(
                                $removedIpAddress->remover->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIpAddress->remover->id,
                                ]
                            ) : h($removedIpAddress->removed_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIpAddress->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
