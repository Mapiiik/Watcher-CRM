<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BorrowedEquipment $borrowedEquipment
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 * @var string[]|\Cake\Collection\CollectionInterface $contracts
 * @var string[]|\Cake\Collection\CollectionInterface $equipmentTypes
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
    <div class="column-responsive column-90">
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
