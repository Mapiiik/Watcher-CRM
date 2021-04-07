<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Billing $billing
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Billings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="billings form content">
            <?= $this->Form->create($billing) ?>
            <fieldset>
                <legend><?= __('Add Billing') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                    echo $this->Form->control('text');
                    echo $this->Form->control('price');
                    echo $this->Form->control('billing_from', ['empty' => true]);
                    echo $this->Form->control('note');
                    echo $this->Form->control('active');
                    echo $this->Form->control('modified_by');
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('billing_until', ['empty' => true]);
                    echo $this->Form->control('separate');
                    echo $this->Form->control('service_id', ['options' => $services, 'empty' => true]);
                    echo $this->Form->control('quantity');
                    echo $this->Form->control('contract_id', ['options' => $contracts]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
