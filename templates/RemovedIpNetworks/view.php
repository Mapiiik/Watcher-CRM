<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpNetwork $removedIpNetwork
 * @var string[]|\Cake\Collection\CollectionInterface $types_of_use
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(
                __('Edit Removed Ip Network'),
                ['action' => 'edit', $removedIpNetwork->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Removed Ip Network'),
                ['action' => 'delete', $removedIpNetwork->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetwork->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __('List Removed Ip Networks'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __('New Removed Ip Network'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="removedIpNetworks view content">
            <h3><?= h($removedIpNetwork->ip_network) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $removedIpNetwork->has('customer') ?
                        $this->Html->link(
                            $removedIpNetwork->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $removedIpNetwork->customer->id]
                        ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $removedIpNetwork->has('contract') ?
                        $this->Html->link(
                            $removedIpNetwork->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $removedIpNetwork->contract->id]
                        ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip Network') ?></th>
                    <td><?= h($removedIpNetwork->ip_network) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type Of Use') ?></th>
                    <td><?= h($types_of_use[$removedIpNetwork->type_of_use]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Range') ?></th>
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
                    <td><?= $removedIpNetwork->has('creator') ? $this->Html->link(
                        $removedIpNetwork->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $removedIpNetwork->creator->id,
                        ]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($removedIpNetwork->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $removedIpNetwork->has('modifier') ? $this->Html->link(
                        $removedIpNetwork->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $removedIpNetwork->modifier->id,
                        ]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed') ?></th>
                    <td><?= h($removedIpNetwork->removed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed By') ?></th>
                    <td><?= $removedIpNetwork->has('remover') ? $this->Html->link(
                        $removedIpNetwork->remover->username,
                        [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $removedIpNetwork->remover->id]
                    ) : '' ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIpNetwork->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
