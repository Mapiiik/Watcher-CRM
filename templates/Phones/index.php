<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Phone[]|\Cake\Collection\CollectionInterface $phones
 */
?>
<div class="phones index content">
    <?= $this->Html->link(__('New Phone'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Phones') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('phone') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phones as $phone): ?>
                <tr>
                    <td><?= $this->Number->format($phone->id) ?></td>
                    <td><?= $phone->has('customer') ? $this->Html->link($phone->customer->title, ['controller' => 'Customers', 'action' => 'view', $phone->customer->id]) : '' ?></td>
                    <td><?= h($phone->phone) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $phone->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $phone->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $phone->id], ['confirm' => __('Are you sure you want to delete # {0}?', $phone->id)]) ?>
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
