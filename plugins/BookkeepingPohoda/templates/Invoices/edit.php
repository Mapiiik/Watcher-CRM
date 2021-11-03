<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $invoice
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $invoice->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $invoice->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Invoices'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __('Edit Invoice') ?></legend>
                <?php
                    echo $this->Form->control('number');
                    echo $this->Form->control('varsym');
                    echo $this->Form->control('date', ['empty' => true]);
                    echo $this->Form->control('maturity', ['empty' => true]);
                    echo $this->Form->control('text');
                    echo $this->Form->control('sum');
                    echo $this->Form->control('debt');
                    echo $this->Form->control('payment_date', ['empty' => true]);
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>