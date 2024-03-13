<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpNetwork $ipNetwork
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit IP Network'),
                ['action' => 'edit', $ipNetwork->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete IP Network'),
                ['action' => 'delete', $ipNetwork->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ipNetwork->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List IP Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New IP Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="ipNetworks view content">
            <h3><?= h($ipNetwork->ip_network) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $ipNetwork->__isset('customer') ?
                                $this->Html->link(
                                    $ipNetwork->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $ipNetwork->customer->id]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $ipNetwork->__isset('customer') ? h($ipNetwork->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $ipNetwork->__isset('contract') ?
                                $this->Html->link(
                                    $ipNetwork->contract->number ?? '--',
                                    ['controller' => 'Contracts', 'action' => 'view', $ipNetwork->contract->id]
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Network') ?></th>
                            <td><?= h($ipNetwork->ip_network) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Type Of Use') ?></th>
                            <td><?= h($ipNetwork->type_of_use->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('IP Address Range') ?></th>
                            <td><?php
                            if (isset($ipNetwork->ip_address_ranges)) {
                                $range = $ipNetwork->ip_address_ranges->first();
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
                            <td><?= h($ipNetwork->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($ipNetwork->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $ipNetwork->__isset('creator') ? $this->Html->link(
                                $ipNetwork->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $ipNetwork->creator->id,
                                ]
                            ) : h($ipNetwork->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($ipNetwork->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $ipNetwork->__isset('modifier') ? $this->Html->link(
                                $ipNetwork->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $ipNetwork->modifier->id,
                                ]
                            ) : h($ipNetwork->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ipNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
