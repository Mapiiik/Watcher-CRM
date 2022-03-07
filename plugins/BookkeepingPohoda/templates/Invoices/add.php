<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $invoice
 * @var \Cake\Collection\CollectionInterface|string[] $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping_pohoda', 'Actions') ?></h4>
            <?= $this->Html->link(
                __d('bookkeeping_pohoda', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __d('bookkeeping_pohoda', 'Add Invoice') ?></legend>
                <?php
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                    echo $this->Form->control('number');
                    echo $this->Form->control('variable_symbol');
                    echo $this->Form->control('creation_date', ['empty' => true]);
                    echo $this->Form->control('due_date', ['empty' => true]);
                    echo $this->Form->control('text');
                    echo $this->Form->control('total');
                    echo $this->Form->control('debt');
                    echo $this->Form->control('payment_date', ['empty' => true]);
                    echo $this->Form->control('send_by_email');
                    echo $this->Form->control('email_sent');
                ?>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping_pohoda', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
