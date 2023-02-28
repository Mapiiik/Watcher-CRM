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
    <div class="column column-90">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __d('bookkeeping_pohoda', 'Add Invoice') ?></legend>
                <?php
                echo $this->Form->control('customer_id', [
                    'label' => __d('bookkeeping_pohoda', 'Customer'),
                    'options' => $customers,
                    'empty' => true,
                ]);
                echo $this->Form->control('number', [
                    'label' => __d('bookkeeping_pohoda', 'Number'),
                ]);
                echo $this->Form->control('variable_symbol', [
                    'label' => __d('bookkeeping_pohoda', 'Variable Symbol'),
                ]);
                echo $this->Form->control('creation_date', [
                    'label' => __d('bookkeeping_pohoda', 'Creation Date'),
                    'empty' => true,
                ]);
                echo $this->Form->control('due_date', [
                    'label' => __d('bookkeeping_pohoda', 'Due Date'),
                    'empty' => true,
                ]);
                echo $this->Form->control('text', [
                    'label' => __d('bookkeeping_pohoda', 'Text'),
                ]);
                echo $this->Form->control('total', [
                    'label' => __d('bookkeeping_pohoda', 'Total'),
                ]);
                echo $this->Form->control('debt', [
                    'label' => __d('bookkeeping_pohoda', 'Debt'),
                ]);
                echo $this->Form->control('payment_date', [
                    'label' => __d('bookkeeping_pohoda', 'Payment Date'),
                    'empty' => true,
                ]);
                echo $this->Form->control('send_by_email', [
                    'label' => __d('bookkeeping_pohoda', 'Send By Email'),
                ]);
                echo $this->Form->control('email_sent', [
                    'label' => __d('bookkeeping_pohoda', 'Email Sent'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping_pohoda', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
