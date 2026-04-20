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
            <?= $this->AuthLink->link(
                __('Edit Sold Equipment'),
                ['action' => 'edit', $soldEquipment->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Sold Equipment'),
                ['action' => 'delete', $soldEquipment->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Sold Equipments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Sold Equipment'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="soldEquipments view content">
            <h3><?= h($soldEquipment->id) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $soldEquipment->customer !== null ? $this->Html->link(
                                $soldEquipment->customer->name ?? '(' . $soldEquipment->customer->id . ')',
                                ['controller' => 'Customers', 'action' => 'view', $soldEquipment->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $soldEquipment->customer !== null ?
                                h($soldEquipment->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $soldEquipment->contract !== null ? $this->Html->link(
                                $soldEquipment->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $soldEquipment->contract->id,
                                    'customer_id' => $soldEquipment->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Equipment Type') ?></th>
                            <td><?= $soldEquipment->equipment_type !== null ? $this->Html->link(
                                $soldEquipment->equipment_type->name ?? '(' . $soldEquipment->equipment_type->id . ')',
                                [
                                    'controller' => 'EquipmentTypes',
                                    'action' => 'view',
                                    $soldEquipment->equipment_type->id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Serial Number') ?></th>
                            <td><?= h($soldEquipment->serial_number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Date Of Sale') ?></th>
                            <td><?= h($soldEquipment->date_of_sale) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $soldEquipment]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
