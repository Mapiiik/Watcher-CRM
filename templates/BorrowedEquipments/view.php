<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BorrowedEquipment $borrowedEquipment
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Borrowed Equipment'), ['action' => 'edit', $borrowedEquipment->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Borrowed Equipment'), ['action' => 'delete', $borrowedEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Borrowed Equipments'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Borrowed Equipment'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="borrowedEquipments view content">
            <h3><?= h($borrowedEquipment->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $borrowedEquipment->has('customer') ? $this->Html->link($borrowedEquipment->customer->name, ['controller' => 'Customers', 'action' => 'view', $borrowedEquipment->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Contract') ?></th>
                    <td><?= $borrowedEquipment->has('contract') ? $this->Html->link($borrowedEquipment->contract->number, ['controller' => 'Contracts', 'action' => 'view', $borrowedEquipment->contract->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Equipment Type') ?></th>
                    <td><?= $borrowedEquipment->has('equipment_type') ? $this->Html->link($borrowedEquipment->equipment_type->name, ['controller' => 'EquipmentTypes', 'action' => 'view', $borrowedEquipment->equipment_type->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Serial Number') ?></th>
                    <td><?= h($borrowedEquipment->serial_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Borrowed From') ?></th>
                    <td><?= h($borrowedEquipment->borrowed_from) ?></td>
                </tr>
                <tr>
                    <th><?= __('Borrowed Until') ?></th>
                    <td><?= h($borrowedEquipment->borrowed_until) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($borrowedEquipment->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($borrowedEquipment->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($borrowedEquipment->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($borrowedEquipment->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($borrowedEquipment->modified_by) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
