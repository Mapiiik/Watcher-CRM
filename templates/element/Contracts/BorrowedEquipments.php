<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<?php if (!empty($contract->borrowed_equipments)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('Equipment Type') ?></th>
            <th><?= __('Serial Number') ?></th>
            <th><?= __('Borrowed From') ?></th>
            <th><?= __('Borrowed Until') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contract->borrowed_equipments as $borrowedEquipment) : ?>
        <tr style="<?= $borrowedEquipment->style ?>">
            <td><?= h($borrowedEquipment->equipment_type->name) ?></td>
            <td><?= h($borrowedEquipment->serial_number) ?></td>
            <td><?= h($borrowedEquipment->borrowed_from) ?></td>
            <td><?= h($borrowedEquipment->borrowed_until) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    [
                        'controller' => 'BorrowedEquipments',
                        'action' => 'view',
                        $borrowedEquipment->id,
                    ]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    [
                        'controller' => 'BorrowedEquipments',
                        'action' => 'edit',
                        $borrowedEquipment->id,
                    ],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    [
                        'controller' => 'BorrowedEquipments',
                        'action' => 'delete',
                        $borrowedEquipment->id,
                    ],
                    [
                        'confirm' => __(
                            'Are you sure you want to delete # {0}?',
                            $borrowedEquipment->id
                        ),
                    ]
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
