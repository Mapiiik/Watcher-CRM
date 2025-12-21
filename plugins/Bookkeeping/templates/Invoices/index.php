<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Bookkeeping\Model\Entity\Invoice> $invoices
 * @var float $total_debt
 * @var float $total_overdue_debt
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __d('bookkeeping', 'Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="invoices index content">
    <?= $this->AuthLink->link(
        __d('bookkeeping', 'New Invoice'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link'],
    ) ?>
    <?= $this->AuthLink->link(
        __d('bookkeeping', 'Send By Email'),
        ['action' => 'sendByEmail'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->AuthLink->link(
        __d('bookkeeping', 'Generate Invoices'),
        ['action' => 'generate'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->AuthLink->link(
        __d('bookkeeping', 'Import Invoices from DBF'),
        ['action' => 'importFromDBF'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->AuthLink->link(
        __d('bookkeeping', 'List Debtors'),
        ['controller' => 'Debtors', 'action' => 'index'],
        ['class' => 'button float-right'],
    ) ?>
    <h3><?= __d('bookkeeping', 'Invoices') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id', __d('bookkeeping', 'Customer')) ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __d('bookkeeping', 'Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('number', __d('bookkeeping', 'Number')) ?></th>
                    <th><?= $this->Paginator->sort(
                        'variable_symbol',
                        __d('bookkeeping', 'Variable Symbol'),
                    ) ?></th>
                    <th><?= $this->Paginator->sort('creation_date', __d('bookkeeping', 'Creation Date')) ?></th>
                    <th><?= $this->Paginator->sort('due_date', __d('bookkeeping', 'Due Date')) ?></th>
                    <th><?= $this->Paginator->sort('total', __d('bookkeeping', 'Total')) ?></th>
                    <th><?= $this->Paginator->sort('debt', __d('bookkeeping', 'Debt')) ?></th>
                    <th><?= $this->Paginator->sort('payment_date', __d('bookkeeping', 'Payment Date')) ?></th>
                    <th><?= $this->Paginator->sort('send_by_email', __d('bookkeeping', 'Send By Email')) ?></th>
                    <th><?= $this->Paginator->sort('email_sent', __d('bookkeeping', 'Email Sent')) ?></th>
                    <th><?= $this->Paginator->sort('created', __d('bookkeeping', 'Created')) ?></th>
                    <th><?= $this->Paginator->sort('modified', __d('bookkeeping', 'Modified')) ?></th>
                    <th class="actions"><?= __d('bookkeeping', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice) : ?>
                <tr style="<?= $invoice->style ?>">
                    <td>
                        <?= $invoice->__isset('customer') ? $this->Html->link(
                            $invoice->customer->name,
                            ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $invoice->customer->id],
                        ) : '' ?>
                    </td>
                    <td><?= $invoice->__isset('customer') ? h($invoice->customer->number) : '' ?></td>
                    <td><?= $this->Number->format($invoice->number) ?></td>
                    <td><?= h($invoice->variable_symbol) ?></td>
                    <td><?= h($invoice->creation_date) ?></td>
                    <td><?= h($invoice->due_date) ?></td>
                    <td><?= $invoice->total === null ? '' : $this->Number->currency($invoice->total->toString()) ?></td>
                    <td><?= $invoice->debt === null ? '' : $this->Number->currency($invoice->debt->toString()) ?></td>
                    <td><?= h($invoice->payment_date) ?></td>
                    <td><?= $invoice->send_by_email ?
                        __d('bookkeeping', 'Yes') : __d('bookkeeping', 'No'); ?></td>
                    <td><?= h($invoice->email_sent) ?></td>
                    <td><?= h($invoice->created) ?></td>
                    <td><?= h($invoice->modified) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __d('bookkeeping', 'Download'),
                            ['action' => 'download', $invoice->id],
                            ['target' => '_blank'],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __d('bookkeeping', 'View'),
                            ['action' => 'view', $invoice->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __d('bookkeeping', 'Edit'),
                            ['action' => 'edit', $invoice->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __d('bookkeeping', 'Delete'),
                            ['action' => 'delete', $invoice->id],
                            ['confirm' => __d(
                                'bookkeeping',
                                'Are you sure you want to delete # {0}?',
                                $invoice->id,
                            )],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <?= __d('bookkeeping', 'Total Debt') . ': ' . $this->Number->currency($total_debt) ?><br>
        <?= __d('bookkeeping', 'Total Overdue Debt') . ': ' . $this->Number->currency($total_overdue_debt) ?><br>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __d('bookkeeping', 'first')) ?>
            <?= $this->Paginator->prev('< ' . __d('bookkeeping', 'previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__d('bookkeeping', 'next') . ' >') ?>
            <?= $this->Paginator->last(__d('bookkeeping', 'last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __d(
                'bookkeeping',
                'Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total',
            ),
        ) ?></p>
    </div>
</div>
