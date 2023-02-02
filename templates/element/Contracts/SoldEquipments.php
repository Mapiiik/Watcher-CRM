<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<?php if (!empty($contract->sold_equipments)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('Equipment Type') ?></th>
            <th><?= __('Serial Number') ?></th>
            <th><?= __('Date Of Sale') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contract->sold_equipments as $soldEquipment) : ?>
        <tr style="<?= $soldEquipment->style ?>">
            <td><?= h($soldEquipment->equipment_type->name) ?></td>
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
