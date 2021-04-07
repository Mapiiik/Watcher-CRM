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
            <?= $this->Html->link(__('Edit Sold Equipment'), ['action' => 'edit', $soldEquipment->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Sold Equipment'), ['action' => 'delete', $soldEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Sold Equipments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Sold Equipment'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="soldEquipments view content">
            <h3><?= h($soldEquipment->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $soldEquipment->has('customer') ? $this->Html->link($soldEquipment->customer->title, ['controller' => 'Customers', 'action' => 'view', $soldEquipment->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $soldEquipment->has('contract') ? $this->Html->link($soldEquipment->contract->id, ['controller' => 'Contracts', 'action' => 'view', $soldEquipment->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Equipment Type') ?></th>
                    <td><?= $soldEquipment->has('equipment_type') ? $this->Html->link($soldEquipment->equipment_type->name, ['controller' => 'EquipmentTypes', 'action' => 'view', $soldEquipment->equipment_type->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Serial Number') ?></th>
                    <td><?= h($soldEquipment->serial_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($soldEquipment->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($soldEquipment->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($soldEquipment->modified_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($soldEquipment->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($soldEquipment->modified) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
