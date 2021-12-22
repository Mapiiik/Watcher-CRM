<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIp[]|\Cake\Collection\CollectionInterface $removedIps
 */
?>
<div class="removedIps index content">
    <?= $this->AuthLink->link(__('New Removed Ip'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Removed Ips') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip') ?></th>
                    <th><?= $this->Paginator->sort('removed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($removedIps as $removedIp): ?>
                <tr style="<?= $removedIp->style ?>">
                    <td><?= $this->Number->format($removedIp->id) ?></td>
                    <td><?= $removedIp->has('customer') ? $this->Html->link($removedIp->customer->name, ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]) : '' ?></td>
                    <td><?= $removedIp->has('contract') ? $this->Html->link($removedIp->contract->number, ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]) : '' ?></td>
                    <td><?= h($removedIp->ip) ?></td>
                    <td><?= h($removedIp->removed) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $removedIp->id]) ?>
                        <?= $this->AuthLink->link(__('Edit'), ['action' => 'edit', $removedIp->id]) ?>
                        <?= $this->AuthLink->postLink(__('Delete'), ['action' => 'delete', $removedIp->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]) ?>
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
