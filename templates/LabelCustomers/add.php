<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\LabelCustomer $labelCustomer
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Label Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="labelCustomers form content">
            <?= $this->Form->create($labelCustomer) ?>
            <fieldset>
                <legend><?= __('Add Label Customer') ?></legend>
                <?php
                    echo $this->Form->control('label_id', ['options' => $labels]);
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                    echo $this->Form->control('note');
                    echo $this->Form->control('created_by');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
