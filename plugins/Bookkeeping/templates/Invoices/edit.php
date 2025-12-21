<?php
/**
 * @var \App\View\AppView $this
 * @var \Bookkeeping\Model\Entity\Invoice $invoice
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('bookkeeping', 'Delete'),
                ['action' => 'delete', $invoice->id],
                [
                    'confirm' => __d('bookkeeping', 'Are you sure you want to delete # {0}?', $invoice->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="invoices form content">
            <?= $this->Form->create($invoice) ?>
            <fieldset>
                <legend><?= __d('bookkeeping', 'Edit Invoice') ?></legend>
                <?php
                echo $this->Form->control('customer_id', [
                    'label' => __d('bookkeeping', 'Customer'),
                    'options' => $customers,
                    'empty' => true,
                ]);
                echo $this->Form->control('number', [
                    'label' => __d('bookkeeping', 'Number'),
                ]);
                echo $this->Form->control('variable_symbol', [
                    'label' => __d('bookkeeping', 'Variable Symbol'),
                ]);
                echo $this->Form->control('creation_date', [
                    'label' => __d('bookkeeping', 'Creation Date'),
                    'empty' => true,
                ]);
                echo $this->Form->control('due_date', [
                    'label' => __d('bookkeeping', 'Due Date'),
                    'empty' => true,
                ]);
                echo $this->Form->control('text', [
                    'label' => __d('bookkeeping', 'Text'),
                ]);
                echo $this->Form->control('total', [
                    'label' => __d('bookkeeping', 'Total'),
                ]);
                echo $this->Form->control('debt', [
                    'label' => __d('bookkeeping', 'Debt'),
                ]);
                echo $this->Form->control('payment_date', [
                    'label' => __d('bookkeeping', 'Payment Date'),
                    'empty' => true,
                ]);
                echo $this->Form->control('send_by_email', [
                    'label' => __d('bookkeeping', 'Send By Email'),
                ]);
                echo $this->Form->control('email_sent', [
                    'label' => __d('bookkeeping', 'Email Sent'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
