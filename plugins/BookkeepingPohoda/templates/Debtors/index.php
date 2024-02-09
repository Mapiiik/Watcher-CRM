<?php
use BookkeepingPohoda\Debtors\Debtor;

/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|iterable<\BookkeepingPohoda\Debtors\Debtor> $debtors
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('allowed_payment_delay', [
            'label' => __d('bookkeeping_pohoda', 'Allowed Payment Delay'),
            'type' => 'number',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('allowed_total_overdue_debt', [
            'label' => __d('bookkeeping_pohoda', 'Allowed Total Overdue Debt'),
            'type' => 'number',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="debtors index content">
    <?= $this->AuthLink->link(
        __d('bookkeeping_pohoda', 'List Invoices'),
        ['controller' => 'Invoices', 'action' => 'index'],
        ['class' => 'button float-right']
    ) ?>
    <?= $this->AuthLink->postLink(
        __d('bookkeeping_pohoda', 'Block Debtors'),
        [
            'plugin' => 'BookkeepingPohoda',
            'controller' => 'Debtors',
            'action' => 'index',
            '?' => $this->getRequest()->getQueryParams(),
        ],
        [
            'data' => [
                'debtors_block' => 1,
            ],
            'class' => 'button float-right',
            'confirm' => __('Are you sure you want to block all listed customers?'),
        ]
    ) ?>
    <?= $this->AuthLink->postLink(
        __d('bookkeeping_pohoda', 'Update Debtors Blocking'),
        [
            'plugin' => 'BookkeepingPohoda',
            'controller' => 'Debtors',
            'action' => 'index',
            '?' => $this->getRequest()->getQueryParams(),
        ],
        [
            'data' => [
                'debtors_block' => 1,
                'debtors_block_clear' => 1,
            ],
            'class' => 'button float-right',
            'confirm' => __('Are you sure you want to block all listed customers and unblock the others?'),
        ]
    ) ?>
    <h3><?= __d('bookkeeping_pohoda', 'Debtors') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __d('bookkeeping_pohoda', 'Customer') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Customer Number') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Emails') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Phones') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Due Date') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Total Debt') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Total Overdue Debt') ?></th>
                    <th><?= __d('bookkeeping_pohoda', 'Invoices') ?></th>
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
                            ]
                        ) ?></td>
                    <td><?= h($debtor->getCustomer()->number) ?></td>
                    <td><?= implode('<br>', array_column($debtor->getCustomer()->emails, 'email')) ?></td>
                    <td><?= implode('<br>', array_column($debtor->getCustomer()->phones, 'phone')) ?></td>
                    <td><?= h($debtor->getDueDate()) ?></td>
                    <td><?= $this->Number->currency($debtor->getTotalDebt()) ?></td>
                    <td><?= $this->Number->currency($debtor->getTotalOverdueDebt()) ?></td>
                    <td><table>
                        <thead>
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
                                <td><?= $invoice->total === null ? '' : $this->Number->currency($invoice->total) ?></td>
                                <td><?= $invoice->debt === null ? '' : $this->Number->currency($invoice->debt) ?></td>
                                <td><?= $invoice->send_by_email ?
                                    __d('bookkeeping_pohoda', 'Yes') : __d('bookkeeping_pohoda', 'No'); ?></td>
                                <td><?= h($invoice->email_sent) ?></td>
                                <td class="actions">
                                    <?= $this->AuthLink->link(
                                        __d('bookkeeping_pohoda', 'Download'),
                                        [
                                            'plugin' => 'BookkeepingPohoda',
                                            'controller' => 'Invoices',
                                            'action' => 'download',
                                            $invoice->id,
                                        ],
                                        ['target' => '_blank']
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
        <?= __d('bookkeeping_pohoda', 'Total Debt') . ': ' . $this->Number->currency($debtors->sumOf(
            function (Debtor $debtor) {
                return $debtor->getTotalDebt();
            }
        )) ?><br>
        <?= __d('bookkeeping_pohoda', 'Total Overdue Debt') . ': ' . $this->Number->currency($debtors->sumOf(
            function (Debtor $debtor) {
                return $debtor->getTotalOverdueDebt();
            }
        )) ?><br>
    </div>
</div>
