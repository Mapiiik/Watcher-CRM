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
                __('Edit Removed Ip'),
                ['action' => 'edit', $removedIp->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Removed Ip'),
                ['action' => 'delete', $removedIp->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Removed Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Removed Ip'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="removedIps view content">
            <h3><?= h($removedIp->ip) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $removedIp->has('customer') ? $this->Html->link(
                        $removedIp->customer->name,
                        ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $removedIp->has('contract') ? $this->Html->link(
                        $removedIp->contract->number,
                        ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Ip') ?></th>
                    <td><?= h($removedIp->ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Type Of Use') ?></th>
                    <td><?= h($types_of_use[$removedIp->type_of_use]) ?></td>
                </tr>
                <tr>
                    <th><?= __('Range') ?></th>
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
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($removedIp->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed') ?></th>
                    <td><?= h($removedIp->removed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed By') ?></th>
                    <td><?= $this->Number->format($removedIp->removed_by) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIp->note)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
