<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $radcheck
 */
?>
<div class="radcheck index content">
    <?= $this->Html->link(__('New Radcheck'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Radcheck') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('username') ?></th>
                    <th><?= $this->Paginator->sort('attribute') ?></th>
                    <th><?= $this->Paginator->sort('op') ?></th>
                    <th><?= $this->Paginator->sort('value') ?></th>
                    <th><?= $this->Paginator->sort('type') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($radcheck as $radcheck): ?>
                <tr>
                    <td><?= $this->Number->format($radcheck->id) ?></td>
                    <td><?= $radcheck->has('customer') ? $this->Html->link($radcheck->customer->name, ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $radcheck->customer->id]) : '' ?></td>
                    <td><?= $radcheck->has('contract') ? $this->Html->link($radcheck->contract->number, ['plugin' => null, 'controller' => 'Contracts', 'action' => 'view', $radcheck->contract->id]) : '' ?></td>
                    <td><?= h($radcheck->username) ?></td>
                    <td><?= h($radcheck->attribute) ?></td>
                    <td><?= h($radcheck->op) ?></td>
                    <td><?= h($radcheck->value) ?></td>
                    <td><?= $this->Number->format($radcheck->type) ?></td>
                    <td><?= h($radcheck->created) ?></td>
                    <td><?= $this->Number->format($radcheck->created_by) ?></td>
                    <td><?= h($radcheck->modified) ?></td>
                    <td><?= $this->Number->format($radcheck->modified_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $radcheck->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $radcheck->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $radcheck->id], ['confirm' => __('Are you sure you want to delete # {0}?', $radcheck->id)]) ?>
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
