<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface[]|\Cake\Collection\CollectionInterface $invoices
 */
?>
<div class="invoices index content">
    <?= $this->Html->link(__('New Invoice'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <?= $this->Html->link(__('Generate Invoices'), ['action' => 'generate'], ['class' => 'button float-right']) ?>
    <?= $this->Html->link(__('Import from DBF'), ['action' => 'importFromDBF'], ['class' => 'button float-right']) ?>
    <h3><?= __('Invoices') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('number') ?></th>
                    <th><?= $this->Paginator->sort('variable_symbol') ?></th>
                    <th><?= $this->Paginator->sort('creation_date') ?></th>
                    <th><?= $this->Paginator->sort('due_date') ?></th>
                    <th><?= $this->Paginator->sort('total') ?></th>
                    <th><?= $this->Paginator->sort('debt') ?></th>
                    <th><?= $this->Paginator->sort('payment_date') ?></th>
                    <th><?= $this->Paginator->sort('send_by_email') ?></th>
                    <th><?= $this->Paginator->sort('email_sent') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) : ?>
                <tr>
                    <td><?= $this->Number->format($invoice->id) ?></td>
                    <td><?= $invoice->has('customer') ? $this->Html->link($invoice->customer->name, ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $invoice->customer->id]) : '' ?></td>
                    <td><?= $this->Number->format($invoice->number) ?></td>
                    <td><?= $this->Number->format($invoice->variable_symbol) ?></td>
                    <td><?= h($invoice->creation_date) ?></td>
                    <td><?= h($invoice->due_date) ?></td>
                    <td><?= $this->Number->format($invoice->total) ?></td>
                    <td><?= $this->Number->format($invoice->debt) ?></td>
                    <td><?= h($invoice->payment_date) ?></td>
                    <td><?= h($invoice->send_by_email) ?></td>
                    <td><?= h($invoice->email_sent) ?></td>
                    <td><?= h($invoice->created) ?></td>
                    <td><?= $this->Number->format($invoice->created_by) ?></td>
                    <td><?= h($invoice->modified) ?></td>
                    <td><?= $this->Number->format($invoice->modified_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Download'), ['action' => 'download', $invoice->id], ['target' => '_blank']) ?>
                        <?= $this->Html->link(__('View'), ['action' => 'view', $invoice->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $invoice->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $invoice->id], ['confirm' => __('Are you sure you want to delete # {0}?', $invoice->id)]) ?>
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
