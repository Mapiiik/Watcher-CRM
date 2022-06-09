<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerLabel[]|\Cake\Collection\CollectionInterface $customerLabels
 */
?>
<div class="customerLabels index content">
    <?= $this->AuthLink->link(
        __('New Customer Label'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Customer Labels') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('label_id') ?></th>
                    <th><?= $this->Paginator->sort('note') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customerLabels as $customerLabel) : ?>
                <tr>
                    <td><?= $this->Number->format($customerLabel->id) ?></td>
                    <td>
                        <?= $customerLabel->has('customer') ? $this->Html->link(
                            $customerLabel->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $customerLabel->customer->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $customerLabel->has('label') ? $this->Html->link(
                            $customerLabel->label->name,
                            ['controller' => 'Labels', 'action' => 'view', $customerLabel->label->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($customerLabel->note) ?></td>
                    <td><?= h($customerLabel->created) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $customerLabel->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $customerLabel->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $customerLabel->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $customerLabel->id)]
                        ) ?>
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
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
