<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SoldEquipment $soldEquipment
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', 'customer_id' => $customer_id, 'contract_id' => $contract_id, $soldEquipment->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Sold Equipments'), ['action' => 'index', 'customer_id' => $customer_id, 'contract_id' => $contract_id], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="soldEquipments form content">
            <?= $this->Form->create($soldEquipment) ?>
            <fieldset>
                <legend><?= __('Edit Sold Equipment') ?></legend>
                <?php
                    if (!isset($customer_id)) echo $this->Form->control('customer_id', ['options' => $customers]);
                    if (!isset($contract_id)) echo $this->Form->control('contract_id', ['options' => $contracts]);
                    echo $this->Form->control('equipment_type_id', ['options' => $equipmentTypes]);
                    echo $this->Form->control('serial_number');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
