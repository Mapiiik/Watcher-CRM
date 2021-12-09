<?php
/**
 * @var \App\View\AppView $this
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="invoices form content">
            <?= $this->Form->create(null, [
                'type' => 'file',
                'valueSources' => ['data', 'query'],
                'url' => [
                    'action' => 'generate',
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Generate Invoices') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('tax_rate_id', [
                            'label' => __('Tax Rate'),
                            'options' => $tax_rates,
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('invoiced_month', [
                            'label' => __('Invoiced Month'),
                            'type' => 'month',
                            'empty' => true,
                            'required' => true,
                        ]);
                        echo $this->Form->control('csv_for_verification', [
                            'label' => __('CSV for verification'),
                            'type' => 'file',
                            'empty' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column-responsive">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
        
        <?php if (isset($verification_data)) : ?>
        <br />
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Customer') ?></th>
                        <th>CRM</th>
                        <th>CSV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verification_data as $customer_number => $customer_comparision) : ?>
                    <tr>
                        <td><?= isset($customer_comparision['customer']) ?
                            $this->Html->link($customer_number . ' - ' . $customer_comparision['customer']->name, [
                                'plugin' => null,
                                'controller' => 'Customers',
                                'action' => 'view',
                                $customer_comparision['customer']->id,
                            ], ['target' => '_blank']) :
                            $this->Html->link($customer_number, [
                                'plugin' => null,
                                'controller' => 'Customers',
                                'action' => 'view',
                                $customer_number - env('CUSTOMER_SERIES', 0),
                            ], ['target' => '_blank']) ?></td>
                        <td>
                            <?php if (isset($customer_comparision['crm'])) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><?= __('Name') ?></th>
                                            <th><?= __('Price') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customer_comparision['crm']['items'] as $item) : ?>
                                        <tr>
                                            <td><?= h($item->name) ?></td>
                                            <td><?= $this->Number->currency($item->period_total) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?= __('Total') . ': ' . $this->Number->currency($customer_comparision['crm']['total']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($customer_comparision['csv'])) : ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th><?= __('Name') ?></th>
                                            <th><?= __('Price') ?></th>
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
                                <?= __('Total') . ': ' . $this->Number->currency($customer_comparision['csv']['total']) ?>
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
