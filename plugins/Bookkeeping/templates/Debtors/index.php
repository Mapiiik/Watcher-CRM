<?php
use Bookkeeping\Debtors\Debtor;

/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface<int, \Bookkeeping\Debtors\Debtor> $debtors
 * @var \Cake\Form\Form $filterForm
 */
?>
<?= $this->Form->create($filterForm, ['type' => 'get', 'valueSources' => ['context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('allowed_payment_delay', [
            'label' => __d('bookkeeping', 'Allowed Payment Delay'),
            'type' => 'number',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('allowed_total_overdue_debt', [
            'label' => __d('bookkeeping', 'Allowed Total Overdue Debt'),
            'type' => 'number',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="debtors index content">
    <?= $this->AuthLink->link(
        __d('bookkeeping', 'List Invoices'),
        ['controller' => 'Invoices', 'action' => 'index'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->AuthLink->postLink(
        __d('bookkeeping', 'Update Debtors Blocking'),
        [
            'plugin' => 'Bookkeeping',
            'controller' => 'Debtors',
            'action' => 'blockingUpdate',
        ],
        [
            'class' => 'button float-right',
            'confirm' => __d(
                'bookkeeping',
                'Are you sure you want to automatically update the debtors blocking?',
            ),
        ],
    ) ?>
    <h3><?= __d('bookkeeping', 'Debtors') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __d('bookkeeping', 'Customer') ?></th>
                    <th><?= __d('bookkeeping', 'Customer Number') ?></th>
                    <th><?= __d('bookkeeping', 'Emails') ?></th>
                    <th><?= __d('bookkeeping', 'Phones') ?></th>
                    <th><?= __d('bookkeeping', 'Due Date') ?></th>
                    <th><?= __d('bookkeeping', 'Total Debt') ?></th>
                    <th><?= __d('bookkeeping', 'Total Overdue Debt') ?></th>
                    <th><?= __d('bookkeeping', 'Invoices') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($debtors as $debtor) : ?>
                <tr>
                    <td><?=
                        $this->Html->link(
                            $debtor->getCustomer()->name,
                            [
                                'plugin' => null,
                                'controller' => 'Customers',
                                'action' => 'view',
                                $debtor->getCustomer()->id,
                            ],
                        ) ?></td>
                    <td><?= h($debtor->getCustomer()->number) ?></td>
                    <td><?= implode('<br>', array_column($debtor->getCustomer()->emails, 'email')) ?></td>
                    <td><?= implode('<br>', array_column($debtor->getCustomer()->phones, 'phone')) ?></td>
                    <td><?= h($debtor->getDueDate()) ?></td>
                    <td><?= $this->Number->currency($debtor->getTotalDebt()) ?></td>
                    <td><?= $this->Number->currency($debtor->getTotalOverdueDebt()) ?></td>
                    <td><table>
                        <thead>
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
                        </thead>
                        <tbody>
                            <?php foreach ($debtor->getInvoices() as $invoice) : ?>
                            <tr>
                                <td><?= $this->Number->format($invoice->number) ?></td>
                                <td><?= h($invoice->variable_symbol) ?></td>
                                <td><?= h($invoice->creation_date) ?></td>
                                <td><?= h($invoice->due_date) ?></td>
                                <td><?= h($invoice->payment_date) ?></td>
                                <td><?= h($invoice->text) ?></td>
                                <td><?= $invoice->total === null
                                    ? '' : $this->Number->currency($invoice->total->toString()) ?></td>
                                <td><?= $invoice->debt === null ?
                                    '' : $this->Number->currency($invoice->debt->toString()) ?></td>
                                <td><?= $invoice->send_by_email ?
                                    __d('bookkeeping', 'Yes') : __d('bookkeeping', 'No'); ?></td>
                                <td><?= h($invoice->email_sent) ?></td>
                                <td class="actions">
                                    <?= $this->AuthLink->link(
                                        __d('bookkeeping', 'Download'),
                                        [
                                            'plugin' => 'Bookkeeping',
                                            'controller' => 'Invoices',
                                            'action' => 'download',
                                            $invoice->id,
                                        ],
                                        ['target' => '_blank'],
                                    ) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <?= __d('bookkeeping', 'Total Debt') . ': ' . $this->Number->currency($debtors->sumOf(
            function (Debtor $debtor) {
                return $debtor->getTotalDebt();
            },
        )) ?><br>
        <?= __d('bookkeeping', 'Total Overdue Debt') . ': ' . $this->Number->currency($debtors->sumOf(
            function (Debtor $debtor) {
                return $debtor->getTotalOverdueDebt();
            },
        )) ?><br>
    </div>
</div>
