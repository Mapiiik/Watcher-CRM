<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ip[]|\Cake\Collection\CollectionInterface $ips
 */
?>
<div class="ips index content">
    <?= $this->Html->link(__('New Ip'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Ips') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
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
                    <th><?= $this->Paginator->sort('router_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ips as $ip): ?>
                <tr>
                    <td><?= h($ip->ip) ?></td>
                    <td><?= $ip->has('customer') ? $this->Html->link($ip->customer->title, ['controller' => 'Customers', 'action' => 'view', $ip->customer->id]) : '' ?></td>
                    <td><?= $ip->has('queue') ? $this->Html->link($ip->queue->name, ['controller' => 'Queues', 'action' => 'view', $ip->queue->id]) : '' ?></td>
                    <td><?= $ip->has('device') ? $this->Html->link($ip->device->name, ['controller' => 'Devices', 'action' => 'view', $ip->device->id]) : '' ?></td>
                    <td><?= h($ip->mac) ?></td>
                    <td><?= $this->Number->format($ip->cost) ?></td>
                    <td><?= $this->Number->format($ip->dealer_id) ?></td>
                    <td><?= h($ip->installation_date) ?></td>
                    <td><?= $ip->has('brokerage') ? $this->Html->link($ip->brokerage->name, ['controller' => 'Brokerages', 'action' => 'view', $ip->brokerage->id]) : '' ?></td>
                    <td><?= h($ip->billing_from) ?></td>
                    <td><?= h($ip->vip) ?></td>
                    <td><?= h($ip->bond) ?></td>
                    <td><?= h($ip->active_until) ?></td>
                    <td><?= $ip->has('router') ? $this->Html->link($ip->router->name, ['controller' => 'Routers', 'action' => 'view', $ip->router->id]) : '' ?></td>
                    <td><?= $ip->has('contract') ? $this->Html->link($ip->contract->id, ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]) : '' ?></td>
                    <td><?= $this->Number->format($ip->id) ?></td>
                    <td><?= h($ip->created) ?></td>
                    <td><?= $this->Number->format($ip->created_by) ?></td>
                    <td><?= h($ip->modified) ?></td>
                    <td><?= $this->Number->format($ip->modified_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $ip->ip]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $ip->ip]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $ip->ip], ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip)]) ?>
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
