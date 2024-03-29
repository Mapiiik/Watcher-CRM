<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<string> $tax_rates
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping_pohoda', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('bookkeeping_pohoda', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
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
                <legend><?= __d('bookkeeping_pohoda', 'Generate Invoices') ?></legend>
                <div class="row">
                    <div class="column">
                    <?php
                        echo $this->Form->control('tax_rate_id', [
                            'label' => __d('bookkeeping_pohoda', 'Tax Rate'),
                            'options' => $tax_rates,
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('invoiced_month', [
                            'label' => __d('bookkeeping_pohoda', 'Invoiced Month'),
                            'placeholder' => __d('bookkeeping_pohoda', 'YYYY-MM'),
                            'type' => 'month',
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('output_format', [
                            'label' => __d('bookkeeping_pohoda', 'Output Format'),
                            'options' => ['xml' => 'Pohoda XML', 'dbf' => 'dBase DBF'],
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('csv_for_verification', [
                            'label' => __d('bookkeeping_pohoda', 'CSV for verification'),
                            'type' => 'file',
                            'empty' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping_pohoda', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>

        <?php if (isset($verification_data)) : ?>
        <br>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __d('bookkeeping_pohoda', 'Customer') ?></th>
                        <th>CRM</th>
                        <th>CSV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verification_data as $customer_number => $customer_comparision) : ?>
                    <tr>
                        <td><?=
                            isset($customer_comparision['customer']) ?
                                $this->Html->link($customer_number . ' - ' . $customer_comparision['customer']->name, [
                                    'plugin' => null,
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $customer_comparision['customer']->id,
                                ], ['target' => '_blank'])
                                :
                                h($customer_number) ?></td>
                        <td>
                            <?php if (isset($customer_comparision['crm'])) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><?= __d('bookkeeping_pohoda', 'Name') ?></th>
                                            <th><?= __d('bookkeeping_pohoda', 'Price') ?></th>
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
                                        <?php foreach ($customer_comparision['crm']['items'] as $item) : ?>
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
                                <?= __d('bookkeeping_pohoda', 'Total')
                                    . ': ' . $this->Number->currency($customer_comparision['crm']['total']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($customer_comparision['csv'])) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><?= __d('bookkeeping_pohoda', 'Name') ?></th>
                                            <th><?= __d('bookkeeping_pohoda', 'Price') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customer_comparision['csv']['items'] as $item) : ?>
                                        <tr>
                                            <td><?= h($item->name) ?></td>
                                            <td><?= $this->Number->currency($item->period_total) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?= __d('bookkeeping_pohoda', 'Total')
                                    . ': ' . $this->Number->currency($customer_comparision['csv']['total']) ?>
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
