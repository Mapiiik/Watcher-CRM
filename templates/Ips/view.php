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
            <?= $this->AuthLink->link(__('Edit Ip'), ['action' => 'edit', $ip->id], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Ip'),
                ['action' => 'delete', $ip->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $ip->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Ip'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="ips view content">
            <h3><?= h($ip->ip) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $ip->has('customer') ? $this->Html->link(
                        $ip->customer->name,
                        ['controller' => 'Customers', 'action' => 'view', $ip->customer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $ip->has('contract') ? $this->Html->link(
                        $ip->contract->number,
                        ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip') ?></th>
                    <td><?= h($ip->ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type Of Use') ?></th>
                    <td><?= h($types_of_use[$ip->type_of_use]) ?></td>
                </tr>
                <tr><?php $device = $ip->routeros_devices->first(); ?>

                    <th><?= __('Device') ?></th>
                    <td><?= isset($device['id']) ?
                            $this->Html->link(
                                $device['system_description'],
                                env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
                                ['target' => '_blank']
                            ) . '<br>' : '' ?></td><?php unset($device); ?>

                </tr>
                <tr>
                    <?php $range = $ip->ip_address_ranges->first(); ?><th><?= __('Range') ?></th>
                    <td><?= isset($range['access_point']['id']) ?
                            __('Access Point') . ': ' . $this->Html->link(
                                $range['access_point']['name'],
                                env('WATCHER_NMS_URL') . '/access-points/view/' . $range['access_point']['id'],
                                ['target' => '_blank']
                            ) . '<br />' : '' ?>
                        <?= isset($range['id']) ?
                            __('Range') . ': ' . $this->Html->link(
                                $range['name'],
                                env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                                ['target' => '_blank']
                            ) . '<br />' : '' ?></td><?php unset($range); ?>

                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($ip->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($ip->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($ip->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($ip->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($ip->modified_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($ip->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
