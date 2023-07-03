<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\SoldEquipment> $soldEquipments
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="soldEquipments index content">
    <?= $this->AuthLink->link(
        __('New Sold Equipment'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Sold Equipments') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('equipment_type_id') ?></th>
                    <th><?= $this->Paginator->sort('serial_number') ?></th>
                    <th><?= $this->Paginator->sort('date_of_sale') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($soldEquipments as $soldEquipment) : ?>
                <tr style="<?= $soldEquipment->style ?>">
                    <td>
                        <?= $soldEquipment->__isset('customer') ? $this->Html->link(
                            $soldEquipment->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $soldEquipment->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $soldEquipment->__isset('customer') ? h($soldEquipment->customer->number) : '' ?></td>
                    <td>
                        <?= $soldEquipment->__isset('contract') ? $this->Html->link(
                            $soldEquipment->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $soldEquipment->contract->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $soldEquipment->__isset('equipment_type') ? $this->Html->link(
                            $soldEquipment->equipment_type->name,
                            ['controller' => 'EquipmentTypes', 'action' => 'view', $soldEquipment->equipment_type->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($soldEquipment->serial_number) ?></td>
                    <td><?= h($soldEquipment->date_of_sale) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $soldEquipment->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $soldEquipment->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $soldEquipment->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id)]
                        ) ?>
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
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
