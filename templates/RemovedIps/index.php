<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIp[]|\Cake\Collection\CollectionInterface $removedIps
 */
?>
<div class="removedIps index content">
    <?= $this->Html->link(__('New Removed Ip'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Removed Ips') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('removed_by') ?></th>
                    <th><?= $this->Paginator->sort('removed') ?></th>
                    <th><?= $this->Paginator->sort('ip') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('queue_id') ?></th>
                    <th><?= $this->Paginator->sort('device_id') ?></th>
                    <th><?= $this->Paginator->sort('mac') ?></th>
                    <th><?= $this->Paginator->sort('cost') ?></th>
                    <th><?= $this->Paginator->sort('dealer_id') ?></th>
                    <th><?= $this->Paginator->sort('installation_date') ?></th>
                    <th><?= $this->Paginator->sort('brokerage_id') ?></th>
                    <th><?= $this->Paginator->sort('billing_from') ?></th>
                    <th><?= $this->Paginator->sort('vip') ?></th>
                    <th><?= $this->Paginator->sort('bond') ?></th>
                    <th><?= $this->Paginator->sort('active_until') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($removedIps as $removedIp): ?>
                <tr>
                    <td><?= $this->Number->format($removedIp->id) ?></td>
                    <td><?= $this->Number->format($removedIp->removed_by) ?></td>
                    <td><?= h($removedIp->removed) ?></td>
                    <td><?= h($removedIp->ip) ?></td>
                    <td><?= $removedIp->has('customer') ? $this->Html->link($removedIp->customer->title, ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]) : '' ?></td>
                    <td><?= $removedIp->has('queue') ? $this->Html->link($removedIp->queue->name, ['controller' => 'Queues', 'action' => 'view', $removedIp->queue->id]) : '' ?></td>
                    <td><?= $removedIp->has('device') ? $this->Html->link($removedIp->device->name, ['controller' => 'Devices', 'action' => 'view', $removedIp->device->id]) : '' ?></td>
                    <td><?= h($removedIp->mac) ?></td>
                    <td><?= $this->Number->format($removedIp->cost) ?></td>
                    <td><?= $this->Number->format($removedIp->dealer_id) ?></td>
                    <td><?= h($removedIp->installation_date) ?></td>
                    <td><?= $removedIp->has('brokerage') ? $this->Html->link($removedIp->brokerage->name, ['controller' => 'Brokerages', 'action' => 'view', $removedIp->brokerage->id]) : '' ?></td>
                    <td><?= h($removedIp->billing_from) ?></td>
                    <td><?= h($removedIp->vip) ?></td>
                    <td><?= h($removedIp->bond) ?></td>
                    <td><?= h($removedIp->active_until) ?></td>
                    <td><?= $removedIp->has('contract') ? $this->Html->link($removedIp->contract->id, ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]) : '' ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $removedIp->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $removedIp->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $removedIp->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
