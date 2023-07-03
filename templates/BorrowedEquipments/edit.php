<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BorrowedEquipment $borrowedEquipment
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface|array<string> $equipmentTypes
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $borrowedEquipment->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Borrowed Equipments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="borrowedEquipments form content">
            <?= $this->Form->create($borrowedEquipment) ?>
            <fieldset>
                <legend><?= __('Edit Borrowed Equipment') ?></legend>
                <?php
                if (!isset($customer_id)) {
                    echo $this->Form->control('customer_id', ['options' => $customers]);
                }
                if (!isset($contract_id)) {
                    echo $this->Form->control('contract_id', ['options' => $contracts]);
                }
                echo $this->Form->control('equipment_type_id', ['options' => $equipmentTypes]);
                echo $this->Form->control('serial_number');
                echo $this->Form->control('borrowed_from', ['empty' => true]);
                echo $this->Form->control('borrowed_until', ['empty' => true]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
