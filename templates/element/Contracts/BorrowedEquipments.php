<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\BorrowedEquipment> $borrowed_equipments
 * @var bool $contract_column
 */
?>
<?php if (!empty($borrowed_equipments)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('Equipment Type') ?></th>
            <th><?= __('Serial Number') ?></th>
            <th><?= __('Borrowed From') ?></th>
            <th><?= __('Borrowed Until') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($borrowed_equipments as $borrowedEquipment) : ?>
        <tr style="<?= $borrowedEquipment->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $borrowedEquipment->__isset('contract') ?
                $this->Html->link(
                    $borrowedEquipment->contract->number,
                    [
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $borrowedEquipment->contract->id,
                    ]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= $borrowedEquipment->__isset('equipment_type') ?
                h($borrowedEquipment->equipment_type->name) : ''
            ?></td>
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
