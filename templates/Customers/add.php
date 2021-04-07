<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('List Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="customers form content">
            <?= $this->Form->create($customer) ?>
            <fieldset>
                <legend><?= __('Add Customer') ?></legend>
                <?php
                    echo $this->Form->control('dealer');
                    echo $this->Form->control('title');
                    echo $this->Form->control('first_name');
                    echo $this->Form->control('last_name');
                    echo $this->Form->control('suffix');
                    echo $this->Form->control('company');
                    echo $this->Form->control('taxe_id', ['options' => $taxes]);
                    echo $this->Form->control('bank_name');
                    echo $this->Form->control('bank_account');
                    echo $this->Form->control('bank_code');
                    echo $this->Form->control('modified_by');
                    echo $this->Form->control('created_by');
                    echo $this->Form->control('ic');
                    echo $this->Form->control('dic');
                    echo $this->Form->control('www');
                    echo $this->Form->control('internal_note');
                    echo $this->Form->control('invoice_delivery');
                    echo $this->Form->control('note');
                    echo $this->Form->control('identity_card_number');
                    echo $this->Form->control('date_of_birth', ['empty' => true]);
                    echo $this->Form->control('termination_date', ['empty' => true]);
                    echo $this->Form->control('agree_gdpr');
                    echo $this->Form->control('agree_mailing_outages');
                    echo $this->Form->control('agree_mailing_commercial');
                    echo $this->Form->control('agree_mailing_billing');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
