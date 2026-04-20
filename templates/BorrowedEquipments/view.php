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
            <?= $this->AuthLink->link(
                __('Edit Borrowed Equipment'),
                ['action' => 'edit', $borrowedEquipment->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Borrowed Equipment'),
                ['action' => 'delete', $borrowedEquipment->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Borrowed Equipments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Borrowed Equipment'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="borrowedEquipments view content">
            <h3><?= h($borrowedEquipment->id) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $borrowedEquipment->customer !== null ? $this->Html->link(
                                $borrowedEquipment->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $borrowedEquipment->customer->id],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $borrowedEquipment->customer !== null ?
                                h($borrowedEquipment->customer->number) : ''
                            ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $borrowedEquipment->contract !== null ? $this->Html->link(
                                $borrowedEquipment->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $borrowedEquipment->contract->id,
                                    'customer_id' => $borrowedEquipment->contract->customer_id,
                                ],
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Equipment Type') ?></th>
                            <td><?= $borrowedEquipment->equipment_type !== null ? $this->Html->link(
                                $borrowedEquipment->equipment_type->name,
                                [
                                    'controller' => 'EquipmentTypes',
                                    'action' => 'view',
                                    $borrowedEquipment->equipment_type->id,
                                ],
                            ) : '' ?></td>
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
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $borrowedEquipment]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
