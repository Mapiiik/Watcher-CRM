<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpNetwork $removedIpNetwork
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Removed IP Network'),
                ['action' => 'edit', $removedIpNetwork->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Removed IP Network'),
                ['action' => 'delete', $removedIpNetwork->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetwork->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Removed IP Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Removed IP Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="removedIpNetworks view content">
            <h3><?= h($removedIpNetwork->ip_network) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $removedIpNetwork->__isset('customer') ?
                                $this->Html->link(
                                    $removedIpNetwork->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $removedIpNetwork->customer->id]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $removedIpNetwork->__isset('customer') ?
                                h($removedIpNetwork->customer->number) : ''
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $removedIpNetwork->__isset('contract') ?
                                $this->Html->link(
                                    $removedIpNetwork->contract->number,
                                    ['controller' => 'Contracts', 'action' => 'view', $removedIpNetwork->contract->id]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Network') ?></th>
                            <td><?= h($removedIpNetwork->ip_network) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($removedIpNetwork->getTypeOfUseName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($removedIpNetwork->ip_address_ranges)) {
                                $range = $removedIpNetwork->ip_address_ranges->first();
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
                            <td><?= $this->Number->format($removedIpNetwork->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($removedIpNetwork->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $removedIpNetwork->__isset('creator') ? $this->Html->link(
                                $removedIpNetwork->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIpNetwork->creator->id,
                                ]
                            ) : h($removedIpNetwork->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($removedIpNetwork->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $removedIpNetwork->__isset('modifier') ? $this->Html->link(
                                $removedIpNetwork->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIpNetwork->modifier->id,
                                ]
                            ) : h($removedIpNetwork->modified_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Removed') ?></th>
                            <td><?= h($removedIpNetwork->removed) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Removed By') ?></th>
                            <td><?= $removedIpNetwork->__isset('remover') ? $this->Html->link(
                                $removedIpNetwork->remover->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $removedIpNetwork->remover->id,
                                ]
                            ) : h($removedIpNetwork->removed_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIpNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
