<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SoldEquipment[] $sold_equipments
 * @var bool $contract_column
 */
?>
<?php if (!empty($sold_equipments)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('Equipment Type') ?></th>
            <th><?= __('Serial Number') ?></th>
            <th><?= __('Date Of Sale') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($sold_equipments as $soldEquipment) : ?>
        <tr style="<?= $soldEquipment->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $soldEquipment->has('contract') ?
                $this->Html->link(
                    $soldEquipment->contract->number,
                    [
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $soldEquipment->contract->id,
                    ]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= $soldEquipment->has('equipment_type') ?
                $this->Html->link(
                    $soldEquipment->equipment_type->name,
                    [
                        'controller' => 'EquipmentTypes',
                        'action' => 'view',
                        $soldEquipment->equipment_type->id,
                    ]
                ) : '' ?></td>
            <td><?= h($soldEquipment->serial_number) ?></td>
            <td><?= h($soldEquipment->date_of_sale) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'SoldEquipments', 'action' => 'view', $soldEquipment->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'SoldEquipments', 'action' => 'edit', $soldEquipment->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    [
                        'controller' => 'SoldEquipments',
                        'action' => 'delete',
                        $soldEquipment->id,
                    ],
                    [
                        'confirm' => __(
                            'Are you sure you want to delete # {0}?',
                            $soldEquipment->id
                        ),
                    ]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
