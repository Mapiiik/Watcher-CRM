<?php
/**
 * @var \App\View\AppView $this
 * @var \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Collection\CollectionInterface $invoices
 * @var bool $show_customers
 */

use Cake\I18n\FrozenDate;
?>
<?php if (is_object($invoices) && !$invoices->isEmpty()) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if ($show_customers) : ?>
            <th><?= __d('bookkeeping_pohoda', 'Customer') ?></th>
            <?php endif; ?>
            <th><?= __d('bookkeeping_pohoda', 'Number') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Variable Symbol') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Creation Date') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Due Date') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Payment Date') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Text') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Total') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Debt') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Send By Email') ?></th>
            <th><?= __d('bookkeeping_pohoda', 'Email Sent') ?></th>
            <th class="actions"><?= __d('bookkeeping_pohoda', 'Actions') ?></th>
        </tr>
        <?php foreach ($invoices as $invoice) : ?>
        <tr style="<?= $invoice->style ?>">
            <?php if ($show_customers) : ?>
            <td>
                <?= $invoice->has('customer') ? $this->Html->link(
                    $invoice->customer->name,
                    ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $invoice->customer->id]
                ) : '' ?>
            </td>
            <?php endif; ?>
            <td><?= $this->Number->format($invoice->number) ?></td>
            <td><?= h($invoice->variable_symbol) ?></td>
            <td><?= h($invoice->creation_date) ?></td>
            <td><?= h($invoice->due_date) ?></td>
            <td><?= h($invoice->payment_date) ?></td>
            <td><?= h($invoice->text) ?></td>
            <td><?= $this->Number->currency($invoice->total) ?></td>
            <td><?= $this->Number->currency($invoice->debt) ?></td>
            <td><?= $invoice->send_by_email ?
                __d('bookkeeping_pohoda', 'Yes') : __d('bookkeeping_pohoda', 'No'); ?></td>
            <td><?= h($invoice->email_sent) ?></td>
            <td class="actions">
                <?= $this->Html->link(
                    __d('bookkeeping_pohoda', 'Download'),
                    ['plugin' => 'BookkeepingPohoda', 'controller' => 'Invoices', 'action' => 'download', $invoice->id],
                    ['target' => '_blank']
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<div>
    <?= __d('bookkeeping_pohoda', 'Total Debt') . ': '
        . $this->Number->currency($invoices->sumOf('debt')) ?><br>
    <?= __d('bookkeeping_pohoda', 'Total Overdue Debt') . ': '
        . $this->Number->currency(
            $invoices
                ->filter(function ($value, $key) {
                    return $value->due_date < FrozenDate::create();
                })
                ->sumOf('debt')
        )
    ?><br>
</div>
<?php endif; ?>
