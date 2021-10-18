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
                        echo $this->Form->control('tax_rate_id', ['options' => $taxes, 'empty' => true]);
                        echo $this->Form->control('month', ['type' => 'month', 'empty' => true]);
                        echo $this->Form->control('csv_check', ['type' => 'file', 'empty' => true]);
                    ?>
                    </div>
                    <div class="column-responsive">
                    <?php
/*                    
                        echo $this->Form->control('effective_date_of_the_amendment', ['empty' => true, 'type' => 'date']);
                        echo $this->Form->control('ssid', ['empty' => true]);
                        echo $this->Form->control('radius_username', ['empty' => true]);
                        echo $this->Form->control('radius_password', ['empty' => true]);
*/
                    ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
