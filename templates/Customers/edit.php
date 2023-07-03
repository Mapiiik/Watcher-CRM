<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer $customer
 * @var \Cake\Collection\CollectionInterface|array<string> $taxRates
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $customer->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Customers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customers form content">
            <?= $this->Form->create($customer) ?>
            <fieldset>
                <legend><?= __('Edit Customer') ?></legend>
                <div class="row">
                    <div class="column">
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
                    <div class="column">
                        <?php
                        echo $this->Form->control('bank_account');
                        echo $this->Form->control('bank_code');
                        echo $this->Form->control('bank_name');
                        echo $this->Form->control('tax_rate_id', [
                            'options' => $taxRates,
                        ]);
                        echo $this->Form->control('dealer', [
                            'options' => $customer->getDealerStateOptions(),
                        ]);
                        echo $this->Form->control('invoice_delivery_type', [
                            'options' => $customer->getInvoiceDeliveryTypeOptions(),
                        ]);
                        echo $this->Form->control('agree_gdpr', [
                            'label' => __('Agrees to Processing of Personal Data'),
                        ]);
                        echo $this->Form->control('agree_mailing_billing', [
                            'label' => __('Agrees to Receive All Correspondence Related to Billing'),
                        ]);
                        echo $this->Form->control('agree_mailing_outages', [
                            'label' => __('Agrees to Receive Information About Outages And Malfunctions'),
                        ]);
                        echo $this->Form->control('agree_mailing_commercial', [
                            'label' => __('Agrees to Receive Commercial Communications'),
                        ]);
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
