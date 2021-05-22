<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $radcheck
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Radcheck'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="radcheck form content">
            <?= $this->Form->create($radcheck) ?>
            <fieldset>
                <legend><?= __('Add Radcheck') ?></legend>
                <?php
                    echo $this->Form->control('username');
                    echo $this->Form->control('attribute');
                    echo $this->Form->control('op');
                    echo $this->Form->control('value');
                    echo $this->Form->control('modified_by');
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('type');
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('contract_id', ['options' => $contracts]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
