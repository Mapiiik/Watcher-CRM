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
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Sold Equipment'),
                ['action' => 'delete', $soldEquipment->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Sold Equipments'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Sold Equipment'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
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
                            <td><?= $soldEquipment->__isset('customer') ? $this->Html->link(
                                $soldEquipment->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $soldEquipment->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $soldEquipment->__isset('customer') ?
                                h($soldEquipment->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $soldEquipment->__isset('contract') ? $this->Html->link(
                                $soldEquipment->contract->number ?? '--',
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $soldEquipment->contract->id,
                                    'customer_id' => $soldEquipment->contract->customer_id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Equipment Type') ?></th>
                            <td><?= $soldEquipment->__isset('equipment_type') ? $this->Html->link(
                                $soldEquipment->equipment_type->name,
                                [
                                    'controller' => 'EquipmentTypes',
                                    'action' => 'view',
                                    $soldEquipment->equipment_type->id,
                                ]
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
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($soldEquipment->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($soldEquipment->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $soldEquipment->__isset('creator') ? $this->Html->link(
                                $soldEquipment->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $soldEquipment->creator->id,
                                ]
                            ) : h($soldEquipment->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($soldEquipment->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $soldEquipment->__isset('modifier') ? $this->Html->link(
                                $soldEquipment->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $soldEquipment->modifier->id,
                                ]
                            ) : h($soldEquipment->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
