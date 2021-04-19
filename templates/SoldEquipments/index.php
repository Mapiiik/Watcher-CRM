<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SoldEquipment[]|\Cake\Collection\CollectionInterface $soldEquipments
 */
?>
<div class="soldEquipments index content">
    <?= $this->Html->link(__('New Sold Equipment'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Sold Equipments') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('equipment_type_id') ?></th>
                    <th><?= $this->Paginator->sort('serial_number') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($soldEquipments as $soldEquipment): ?>
                <tr>
                    <td><?= $this->Number->format($soldEquipment->id) ?></td>
                    <td><?= $soldEquipment->has('customer') ? $this->Html->link($soldEquipment->customer->title, ['controller' => 'Customers', 'action' => 'view', $soldEquipment->customer->id]) : '' ?></td>
                    <td><?= $soldEquipment->has('contract') ? $this->Html->link($soldEquipment->contract->number, ['controller' => 'Contracts', 'action' => 'view', $soldEquipment->contract->id]) : '' ?></td>
                    <td><?= $soldEquipment->has('equipment_type') ? $this->Html->link($soldEquipment->equipment_type->name, ['controller' => 'EquipmentTypes', 'action' => 'view', $soldEquipment->equipment_type->id]) : '' ?></td>
                    <td><?= h($soldEquipment->serial_number) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $soldEquipment->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $soldEquipment->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $soldEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
