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
            <?= $this->Html->link(__('Edit Removed Ip'), ['action' => 'edit', $removedIp->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Removed Ip'), ['action' => 'delete', $removedIp->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Removed Ips'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Removed Ip'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="removedIps view content">
            <h3><?= h($removedIp->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Ip') ?></th>
                    <td><?= h($removedIp->ip) ?></td>
                </tr>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $removedIp->has('customer') ? $this->Html->link($removedIp->customer->title, ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Queue') ?></th>
                    <td><?= $removedIp->has('queue') ? $this->Html->link($removedIp->queue->name, ['controller' => 'Queues', 'action' => 'view', $removedIp->queue->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Device') ?></th>
                    <td><?= $removedIp->has('device') ? $this->Html->link($removedIp->device->name, ['controller' => 'Devices', 'action' => 'view', $removedIp->device->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Mac') ?></th>
                    <td><?= h($removedIp->mac) ?></td>
                </tr>
                <tr>
                    <th><?= __('Brokerage') ?></th>
                    <td><?= $removedIp->has('brokerage') ? $this->Html->link($removedIp->brokerage->name, ['controller' => 'Brokerages', 'action' => 'view', $removedIp->brokerage->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $removedIp->has('contract') ? $this->Html->link($removedIp->contract->id, ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($removedIp->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed By') ?></th>
                    <td><?= $this->Number->format($removedIp->removed_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Cost') ?></th>
                    <td><?= $this->Number->format($removedIp->cost) ?></td>
                </tr>
                <tr>
                    <th><?= __('Dealer Id') ?></th>
                    <td><?= $this->Number->format($removedIp->dealer_id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Removed') ?></th>
                    <td><?= h($removedIp->removed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Installation Date') ?></th>
                    <td><?= h($removedIp->installation_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Billing From') ?></th>
                    <td><?= h($removedIp->billing_from) ?></td>
                </tr>
                <tr>
                    <th><?= __('Bond') ?></th>
                    <td><?= h($removedIp->bond) ?></td>
                </tr>
                <tr>
                    <th><?= __('Active Until') ?></th>
                    <td><?= h($removedIp->active_until) ?></td>
                </tr>
                <tr>
                    <th><?= __('Vip') ?></th>
                    <td><?= $removedIp->vip ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Comment') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIp->comment)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIp->note)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Access Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($removedIp->access_description)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
