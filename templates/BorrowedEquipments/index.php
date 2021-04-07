<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BorrowedEquipment[]|\Cake\Collection\CollectionInterface $borrowedEquipments
 */
?>
<div class="borrowedEquipments index content">
    <?= $this->Html->link(__('New Borrowed Equipment'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Borrowed Equipments') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('equipment_type_id') ?></th>
                    <th><?= $this->Paginator->sort('serial_number') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowedEquipments as $borrowedEquipment): ?>
                <tr>
                    <td><?= $this->Number->format($borrowedEquipment->id) ?></td>
                    <td><?= $borrowedEquipment->has('customer') ? $this->Html->link($borrowedEquipment->customer->title, ['controller' => 'Customers', 'action' => 'view', $borrowedEquipment->customer->id]) : '' ?></td>
                    <td><?= $borrowedEquipment->has('contract') ? $this->Html->link($borrowedEquipment->contract->id, ['controller' => 'Contracts', 'action' => 'view', $borrowedEquipment->contract->id]) : '' ?></td>
                    <td><?= $borrowedEquipment->has('equipment_type') ? $this->Html->link($borrowedEquipment->equipment_type->name, ['controller' => 'EquipmentTypes', 'action' => 'view', $borrowedEquipment->equipment_type->id]) : '' ?></td>
                    <td><?= h($borrowedEquipment->serial_number) ?></td>
                    <td><?= h($borrowedEquipment->created) ?></td>
                    <td><?= $this->Number->format($borrowedEquipment->created_by) ?></td>
                    <td><?= h($borrowedEquipment->modified) ?></td>
                    <td><?= $this->Number->format($borrowedEquipment->modified_by) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $borrowedEquipment->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $borrowedEquipment->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $borrowedEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id)]) ?>
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
