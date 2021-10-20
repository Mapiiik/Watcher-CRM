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
                'valueSources' => ['query', 'context'],
                'url' => [
                    'action' => 'generate',
                    '_ext' => 'dbf'
                ]
            ]) ?>
            <fieldset>
                <legend><?= __('Generate Invoices') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('tax_rate_id', ['label' => __('Tax Rate'), 'options' => $taxes, 'empty' => true, 'required' => true]);
                        echo $this->Form->control('invoiced_month', ['label' => __('Invoiced Month'), 'type' => 'month', 'empty' => true, 'required' => true]);
                        echo $this->Form->control('csv_for_verification', ['label' => __('CSV for verification'), 'type' => 'file', 'empty' => true]);
                    ?>
                    </div>
                    <div class="column-responsive">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
