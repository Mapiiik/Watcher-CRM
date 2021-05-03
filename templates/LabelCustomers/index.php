<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LabelCustomer[]|\Cake\Collection\CollectionInterface $labelCustomers
 */
?>
<div class="labelCustomers index content">
    <?= $this->Html->link(__('New Label Customer'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Label Customers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('label_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($labelCustomers as $labelCustomer): ?>
                <tr>
                    <td><?= $labelCustomer->has('label') ? $this->Html->link($labelCustomer->label->name, ['controller' => 'Labels', 'action' => 'view', $labelCustomer->label->id]) : '' ?></td>
                    <td><?= $labelCustomer->has('customer') ? $this->Html->link($labelCustomer->customer->name, ['controller' => 'Customers', 'action' => 'view', $labelCustomer->customer->id]) : '' ?></td>
                    <td><?= h($labelCustomer->created) ?></td>
                    <td><?= $this->Number->format($labelCustomer->id) ?></td>
                    <td><?= $this->Number->format($labelCustomer->created_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $labelCustomer->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $labelCustomer->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $labelCustomer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $labelCustomer->id)]) ?>
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
