<?php

use App\Model\Entity\Billing;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $billings
 * @var \Cake\Collection\CollectionInterface|array<string> $services
 * @var \Cake\Collection\CollectionInterface|array<string> $accessPoints
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Settings'),
                ['controller' => 'Settings', 'action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List Billings'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="billings form content">
            <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
            <fieldset>
                <legend><?= __('Original Service') ?></legend>
                <?= $this->Form->control('original_service_id', [
                    'label' => __('Service'),
                    'options' => $services,
                    'empty' => true,
                    'required' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('active_on_date', [
                    'label' => __('Active on Date'),
                    'type' => 'date',
                    'required' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('processing_limit', [
                    'label' => __('Processing Limit'),
                    'type' => 'number',
                    'default' => 100,
                    'required' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('standard_prices_only', [
                    'label' => __('Standard Prices Only'),
                    'type' => 'checkbox',
                    'default' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('price', [
                    'label' => __('Price'),
                    'type' => 'number',
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('fixed_discount', [
                    'label' => __('Fixed Discount'),
                    'type' => 'number',
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('percentage_discount', [
                    'label' => __('Percentage Discount'),
                    'type' => 'number',
                    'onchange' => 'this.form.submit();',
                ]) ?>
                <?= $this->Form->control('access_point_id', [
                    'label' => __('Access Point'),
                    'options' => $accessPoints,
                    'empty' => true,
                    'onchange' => 'this.form.submit();',
                ]) ?>
            </fieldset>
            <?= $this->Form->end() ?>

            <hr />

            <?= $this->Form->create(new Billing()) ?>
                <fieldset>
                    <legend><?= __('New Service') ?></legend>
                    <?php
                    echo $this->Form->control('service_id', [
                        'label' => __('Service'),
                        'options' => $services,
                        'empty' => true,
                        'required' => true,
                    ]);
                    echo $this->Form->control('billing_from', [
                        'empty' => true,
                        'required' => true,
                    ]);
                    ?>
                </fieldset>
                <fieldset>
                    <?= $this->Form->control('price', [
                        'label' => __('Price'),
                        'type' => 'number',
                    ]) ?>
                    <?= $this->Form->control('fixed_discount', [
                        'label' => __('Fixed Discount'),
                        'type' => 'number',
                    ]) ?>
                    <?= $this->Form->control('percentage_discount', [
                        'label' => __('Percentage Discount'),
                        'type' => 'number',
                    ]) ?>
                </fieldset>
                <?= $this->Form->button(
                    __('Submit'),
                    [
                        'confirm' => __(
                            'Do you really want to change the original service to the new service'
                            . ' for all the billings listed below from the date set?'
                        ),
                    ]
                ) ?>
                <?= $this->Form->end() ?>
            </div>
            <hr />
            <div class="billings index content">
                <h3><?= __('Billings') . ' - ' . __('Bulk Service Change') ?></h3>
                <?= $this->element('Contracts/Billings', [
                    'billings' => $billings,
                    'customer_column' => true,
                    'contract_column' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>
