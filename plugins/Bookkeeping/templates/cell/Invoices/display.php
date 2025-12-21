<?php

use Bookkeeping\Model\Entity\Invoice;
use Cake\I18n\Date;

/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet<int, \Bookkeeping\Model\Entity\Invoice> $invoices
 * @var bool $show_customers
 */
?>
<div class="table-responsive">
    <?php if (is_object($invoices) && !$invoices->isEmpty()) : ?>
    <table>
        <tr>
            <?php if ($show_customers) : ?>
            <th><?= __d('bookkeeping', 'Customer') ?></th>
            <th><?= __d('bookkeeping', 'Customer Number') ?></th>
            <?php endif; ?>
            <th><?= __d('bookkeeping', 'Number') ?></th>
            <th><?= __d('bookkeeping', 'Variable Symbol') ?></th>
            <th><?= __d('bookkeeping', 'Creation Date') ?></th>
            <th><?= __d('bookkeeping', 'Due Date') ?></th>
            <th><?= __d('bookkeeping', 'Payment Date') ?></th>
            <th><?= __d('bookkeeping', 'Text') ?></th>
            <th><?= __d('bookkeeping', 'Total') ?></th>
            <th><?= __d('bookkeeping', 'Debt') ?></th>
            <th><?= __d('bookkeeping', 'Send By Email') ?></th>
            <th><?= __d('bookkeeping', 'Email Sent') ?></th>
            <th class="actions"><?= __d('bookkeeping', 'Actions') ?></th>
        </tr>
        <?php foreach ($invoices as $invoice) : ?>
            <?php
            /** @var \Bookkeeping\Model\Entity\Invoice $invoice */
            ?>
        <tr style="<?= $invoice->style ?>">
            <?php if ($show_customers) : ?>
            <td>
                <?= $invoice->__isset('customer') ? $this->Html->link(
                    $invoice->customer->name,
                    ['plugin' => null, 'controller' => 'Customers', 'action' => 'view', $invoice->customer->id],
                ) : '' ?>
            </td>
            <td><?= $invoice->__isset('customer') ? h($invoice->customer->number) : '' ?></td>
            <?php endif; ?>
            <td><?= $this->Number->format($invoice->number) ?></td>
            <td><?= h($invoice->variable_symbol) ?></td>
            <td><?= h($invoice->creation_date) ?></td>
            <td><?= h($invoice->due_date) ?></td>
            <td><?= h($invoice->payment_date) ?></td>
            <td><?= h($invoice->text) ?></td>
            <td><?= $invoice->total === null ? '' : $this->Number->currency($invoice->total->toString()) ?></td>
            <td><?= $invoice->debt === null ? '' : $this->Number->currency($invoice->debt->toString()) ?></td>
            <td><?= $invoice->send_by_email ?
                __d('bookkeeping', 'Yes') : __d('bookkeeping', 'No'); ?></td>
            <td><?= h($invoice->email_sent) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __d('bookkeeping', 'Download'),
                    ['plugin' => 'Bookkeeping', 'controller' => 'Invoices', 'action' => 'download', $invoice->id],
                    ['target' => '_blank'],
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <div class="float-right">
        <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query']]) ?>
        <?= $this->Form->control('show_also_paid_invoices', [
            'label' => __d('bookkeeping', 'Show also paid invoices'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) ?>
        <?= $this->Form->end() ?>
    </div>
    <div>
        <?= __d('bookkeeping', 'Total Debt') . ': '
            . $this->Number->currency($invoices->sumOf(
                function (Invoice $invoice) {
                    return $invoice->debt->toFloat();
                },
            )) ?><br>
        <?= __d('bookkeeping', 'Total Overdue Debt') . ': '
            . $this->Number->currency(
                $invoices
                    ->filter(function ($value, $_key) {
                        return $value->due_date < Date::now();
                    })
                    ->sumOf(
                        function (Invoice $invoice) {
                            return $invoice->debt->toFloat();
                        },
                    ),
            )
        ?>
    </div>
</div>
