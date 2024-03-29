<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceType $serviceType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $serviceType->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $serviceType->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(__('List Service Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="serviceTypes form content">
            <?= $this->Form->create($serviceType) ?>
            <fieldset>
                <legend><?= __('Edit Service Type') ?></legend>
                <?php
                echo $this->Form->control('name');
                echo $this->Form->control('contract_number_format');
                echo $this->Form->control('subscriber_verification_code_format');
                echo $this->Form->control('activation_fee');
                echo $this->Form->control('activation_fee_with_obligation');
                echo $this->Form->control('invoice_text');
                echo $this->Form->control('separate_invoice');
                echo $this->Form->control('invoice_with_items');
                echo $this->Form->control('installation_address_required');
                echo $this->Form->control('access_point_required');
                echo $this->Form->control('normally_with_borrowed_equipment');
                echo $this->Form->control('have_contract_versions');
                echo $this->Form->control('have_equipments');
                echo $this->Form->control('have_ip_addresses', ['label' => __('Have IP Addresses')]);
                echo $this->Form->control('have_radius_accounts', ['label' => __('Have RADIUS Accounts')]);
                echo $this->Form->control('assign_ip_addresses_from_behind', [
                    'label' => __('Assign IP addresses from behind'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
