<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $accountingProfiles
 * @var array<string, array{
 *   csv?: array{total: \PhpCollective\DecimalObject\Decimal, items: array},
 *   crm?: array{total: \PhpCollective\DecimalObject\Decimal, items: array},
 *   customer?: \App\Model\Entity\Customer
 * }> $verificationData
 */

use Bookkeeping\Model\Enum\InvoiceExportFormat;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="invoices form content">
            <?= $this->Form->create(null, [
                'type' => 'file',
                'valueSources' => ['data', 'query'],
                'url' => [
                    'action' => 'generate',
                ],
            ]) ?>
            <fieldset>
                <legend><?= __d('bookkeeping', 'Generate Invoices') ?></legend>
                <div class="row">
                    <div class="column">
                    <?php
                        echo $this->Form->control('accounting_profile_id', [
                            'label' => __d('bookkeeping', 'Accounting Profile'),
                            'options' => $accountingProfiles,
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('invoiced_month', [
                            'label' => __d('bookkeeping', 'Invoiced Month'),
                            'placeholder' => __d('bookkeeping', 'YYYY-MM'),
                            'type' => 'month',
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('output_format', [
                            'label' => __d('bookkeeping', 'Output Format'),
                            'options' => InvoiceExportFormat::options(),
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('csv_for_verification', [
                            'label' => __d('bookkeeping', 'CSV for verification'),
                            'type' => 'file',
                            'empty' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>

        <?php if (isset($verificationData)) : ?>
        <br>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __d('bookkeeping', 'Customer') ?></th>
                        <th>
                            CRM
                            <span style="color: red; font-weight: normal; float:right;">
                                <?= __d(
                                    'bookkeeping',
                                    'A price in red indicates a non-standard price.',
                                ) ?>
                            </span>
                        </th>
                        <th>CSV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verificationData as $customerNumber => $customerComparision) : ?>
                    <tr>
                        <td><?=
                            isset($customerComparision['customer']) ?
                                $this->Html->link($customerNumber . ' - ' . $customerComparision['customer']->name, [
                                    'plugin' => null,
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $customerComparision['customer']->id,
                                ], ['target' => '_blank'])
                                :
                                h($customerNumber) ?></td>
                        <td>
                            <?php if (isset($customerComparision['crm'])) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><?= __d('bookkeeping', 'Name') ?></th>
                                            <th><?= __d('bookkeeping', 'Price') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        /**
                                         * List invoices from CRM (non-standard prices in red)
                                         *
                                         * @var \App\Model\Entity\Billing $item
                                         * */
                                        ?>
                                        <?php foreach ($customerComparision['crm']['items'] as $item) : ?>
                                        <tr>
                                            <td><?= h($item->name) ?></td>
                                            <td style="<?=
                                                !empty($item->price)
                                                || !empty($item->fixed_discount)
                                                || !empty($item->percentage_discount)
                                                    ? 'color: red;'
                                                    : ''
                                            ?>"><?= $this->Number->currency($item->period_total) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?= __d('bookkeeping', 'Total')
                                    . ': ' . $this->Number->currency($customerComparision['crm']['total']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($customerComparision['csv'])) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><?= __d('bookkeeping', 'Name') ?></th>
                                            <th><?= __d('bookkeeping', 'Price') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customerComparision['csv']['items'] as $item) : ?>
                                        <tr>
                                            <td><?= h($item->name) ?></td>
                                            <td><?= $this->Number->currency($item->period_total) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?= __d('bookkeeping', 'Total')
                                    . ': ' . $this->Number->currency($customerComparision['csv']['total']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
