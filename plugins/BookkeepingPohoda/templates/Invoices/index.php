<?php
/**
 * @var \App\View\AppView $this
 * @var \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Collection\CollectionInterface $invoices
 */

?>
<div class="invoices index content">
    <?= $this->Html->link(
        __d('bookkeeping_pohoda', 'New Invoice'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <?= $this->Html->link(
        __d('bookkeeping_pohoda', 'Send By Email'),
        ['action' => 'sendByEmail'],
        ['class' => 'button float-right']
    ) ?>
    <?= $this->Html->link(
        __d('bookkeeping_pohoda', 'Generate Invoices'),
        ['action' => 'generate'],
        ['class' => 'button float-right']
    ) ?>
    <?= $this->Html->link(
        __d('bookkeeping_pohoda', 'Import Invoices from DBF'),
        ['action' => 'importFromDBF'],
        ['class' => 'button float-right']
    ) ?>
    <h3><?= __d('bookkeeping_pohoda', 'Invoices') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id', __d('bookkeeping_pohoda', 'Id')) ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __d('bookkeeping_pohoda', 'Customer')) ?></th>
                    <th><?= $this->Paginator->sort('number', __d('bookkeeping_pohoda', 'Number')) ?></th>
                    <th><?= $this->Paginator->sort(
                        'variable_symbol',
                        __d('bookkeeping_pohoda', 'Variable Symbol')
                    ) ?></th>
                    <th><?= $this->Paginator->sort('creation_date', __d('bookkeeping_pohoda', 'Creation Date')) ?></th>
                    <th><?= $this->Paginator->sort('due_date', __d('bookkeeping_pohoda', 'Due Date')) ?></th>
                    <th><?= $this->Paginator->sort('total', __d('bookkeeping_pohoda', 'Total')) ?></th>
                    <th><?= $this->Paginator->sort('debt', __d('bookkeeping_pohoda', 'Debt')) ?></th>
                    <th><?= $this->Paginator->sort('payment_date', __d('bookkeeping_pohoda', 'Payment Date')) ?></th>
                    <th><?= $this->Paginator->sort('send_by_email', __d('bookkeeping_pohoda', 'Send By Email')) ?></th>
                    <th><?= $this->Paginator->sort('email_sent', __d('bookkeeping_pohoda', 'Email Sent')) ?></th>
                    <th><?= $this->Paginator->sort('created', __d('bookkeeping_pohoda', 'Created')) ?></th>
                    <th><?= $this->Paginator->sort('created_by', __d('bookkeeping_pohoda', 'Created By')) ?></th>
                    <th><?= $this->Paginator->sort('modified', __d('bookkeeping_pohoda', 'Modified')) ?></th>
                    <th><?= $this->Paginator->sort('modified_by', __d('bookkeeping_pohoda', 'Modified By')) ?></th>
                    <th class="actions"><?= __d('bookkeeping_pohoda', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) : ?>
                <tr style="<?= $invoice->style ?>">
                    <td><?= $this->Number->format($invoice->id) ?></td>
                    <td>
                        <?= $invoice->has('customer') ? $this->Html->link(
                            $invoice->customer->name,
                            ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $invoice->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $this->Number->format($invoice->number) ?></td>
                    <td><?= h($invoice->variable_symbol) ?></td>
                    <td><?= h($invoice->creation_date) ?></td>
                    <td><?= h($invoice->due_date) ?></td>
                    <td><?= $this->Number->currency($invoice->total) ?></td>
                    <td><?= $this->Number->currency($invoice->debt) ?></td>
                    <td><?= h($invoice->payment_date) ?></td>
                    <td><?= $invoice->send_by_email ?
                        __d('bookkeeping_pohoda', 'Yes') : __d('bookkeeping_pohoda', 'No'); ?></td>
                    <td><?= h($invoice->email_sent) ?></td>
                    <td><?= h($invoice->created) ?></td>
                    <td><?= $this->Number->format($invoice->created_by) ?></td>
                    <td><?= h($invoice->modified) ?></td>
                    <td><?= $this->Number->format($invoice->modified_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(
                            __d('bookkeeping_pohoda', 'Download'),
                            ['action' => 'download', $invoice->id],
                            ['target' => '_blank']
                        ) ?>
                        <?= $this->Html->link(__d('bookkeeping_pohoda', 'View'), ['action' => 'view', $invoice->id]) ?>
                        <?= $this->Html->link(
                            __d('bookkeeping_pohoda', 'Edit'),
                            ['action' => 'edit', $invoice->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __d('bookkeeping_pohoda', 'Delete'),
                            ['action' => 'delete', $invoice->id],
                            ['confirm' => __d(
                                'bookkeeping_pohoda',
                                'Are you sure you want to delete # {0}?',
                                $invoice->id
                            )]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <?= __d('bookkeeping_pohoda', 'Total Debt') . ': ' . $this->Number->currency($total_debt) ?><br>
        <?= __d('bookkeeping_pohoda', 'Total Overdue Debt') . ': ' . $this->Number->currency($total_overdue_debt) ?><br>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __d('bookkeeping_pohoda', 'first')) ?>
            <?= $this->Paginator->prev('< ' . __d('bookkeeping_pohoda', 'previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__d('bookkeeping_pohoda', 'next') . ' >') ?>
            <?= $this->Paginator->last(__d('bookkeeping_pohoda', 'last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __d(
                'bookkeeping_pohoda',
                'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'
            )
        ) ?></p>
    </div>
</div>
