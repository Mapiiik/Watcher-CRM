<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ip $ip
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit IP Address'),
                ['action' => 'edit', $ip->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete IP Address'),
                ['action' => 'delete', $ip->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ip->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List IP Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New IP Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ips view content">
            <h3><?= h($ip->ip) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $ip->__isset('customer') ? $this->Html->link(
                                $ip->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $ip->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $ip->__isset('customer') ? h($ip->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $ip->__isset('contract') ? $this->Html->link(
                                $ip->contract->number ?? '--',
                                ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address') ?></th>
                            <td><?= h($ip->ip) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($ip->getTypeOfUseName()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Device') ?></th>
                            <td><?php
                            if (isset($ip->routeros_devices)) {
                                $device = $ip->routeros_devices->first();
                                echo isset($device['id']) ?
                                    $this->Html->link(
                                        $device['system_description'],
                                        env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                unset($device);
                            }
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($ip->ip_address_ranges)) {
                                $range = $ip->ip_address_ranges->first();
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
                            <td><?= h($ip->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($ip->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $ip->__isset('creator') ? $this->Html->link(
                                $ip->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $ip->creator->id,
                                ]
                            ) : h($ip->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($ip->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $ip->__isset('modifier') ? $this->Html->link(
                                $ip->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $ip->modifier->id,
                                ]
                            ) : h($ip->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ip->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
