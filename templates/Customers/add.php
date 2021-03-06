<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var string[]|\Cake\Collection\CollectionInterface $taxRates
 * @var string[]|\Cake\Collection\CollectionInterface $invoice_delivery_types
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="customers form content">
            <?= $this->Form->create($customer) ?>
            <fieldset>
                <legend><?= __('Add Customer') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('company');
                        echo $this->Form->control('title');
                        echo $this->Form->control('first_name');
                        echo $this->Form->control('last_name');
                        echo $this->Form->control('suffix');
                        echo $this->Form->control('date_of_birth', ['empty' => true]);
                        echo $this->Form->control('identity_card_number');
                        echo $this->Form->control('ic');
                        echo $this->Form->control('dic');
                        echo $this->Form->control('www');
                    ?>
                    </div>
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('bank_account');
                        echo $this->Form->control('bank_code');
                        echo $this->Form->control('bank_name');
                        echo $this->Form->control('tax_rate_id', ['options' => $taxRates]);
                        echo $this->Form->control('termination_date', ['empty' => true]);
                        echo $this->Form->control('dealer');
                        echo $this->Form->control('invoice_delivery_type', ['options' => $invoice_delivery_types]);
                        echo $this->Form->control('agree_gdpr');
                        echo $this->Form->control('agree_mailing_billing');
                        echo $this->Form->control('agree_mailing_outages');
                        echo $this->Form->control('agree_mailing_commercial');
                    ?>
                    </div>
                </div>
                <?php
                    echo $this->Form->control('note');
                    echo $this->Form->control('internal_note');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
