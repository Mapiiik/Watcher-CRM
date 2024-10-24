<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\EquipmentType $equipmentType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Equipment Type'),
                ['action' => 'edit', $equipmentType->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Equipment Type'),
                ['action' => 'delete', $equipmentType->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $equipmentType->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Equipment Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Equipment Type'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="equipmentTypes view content">
            <h3><?= h($equipmentType->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($equipmentType->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Price') ?></th>
                            <td><?= $equipmentType->price === null ?
                                '' : $this->Number->currency($equipmentType->price->toString()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Price With Obligation') ?></th>
                            <td><?= $equipmentType->price_with_obligation === null ?
                                '' : $this->Number->currency($equipmentType->price_with_obligation->toString()) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($equipmentType->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($equipmentType->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $equipmentType->__isset('creator') ? $this->Html->link(
                                $equipmentType->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $equipmentType->creator->id,
                                ]
                            ) : h($equipmentType->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($equipmentType->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $equipmentType->__isset('modifier') ? $this->Html->link(
                                $equipmentType->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $equipmentType->modifier->id,
                                ]
                            ) : h($equipmentType->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Borrowed Equipments') ?></h4>
                <?php if (!empty($equipmentType->borrowed_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Borrowed From') ?></th>
                            <th><?= __('Borrowed Until') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($equipmentType->borrowed_equipments as $borrowedEquipment) : ?>
                        <tr style="<?= $borrowedEquipment->style ?>">
                            <td><?= $borrowedEquipment->__isset('customer') ?
                                $this->Html->link(
                                    $borrowedEquipment->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $borrowedEquipment->customer->id]
                                ) : '' ?></td>
                            <td><?= $borrowedEquipment->__isset('customer') ?
                                h($borrowedEquipment->customer->number) : ''
                            ?></td>
                            <td><?= $borrowedEquipment->__isset('contract') ?
                                $this->Html->link(
                                    $borrowedEquipment->contract->number ?? '--',
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $borrowedEquipment->contract->id,
                                        'customer_id' => $borrowedEquipment->contract->customer_id,
                                    ]
                                ) : '' ?></td>
                            <td><?= h($borrowedEquipment->serial_number) ?></td>
                            <td><?= h($borrowedEquipment->borrowed_from) ?></td>
                            <td><?= h($borrowedEquipment->borrowed_until) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'BorrowedEquipments', 'action' => 'view', $borrowedEquipment->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'BorrowedEquipments', 'action' => 'edit', $borrowedEquipment->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    [
                                        'controller' => 'BorrowedEquipments',
                                        'action' => 'delete',
                                        $borrowedEquipment->id,
                                    ],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Sold Equipments') ?></h4>
                <?php if (!empty($equipmentType->sold_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Date Of Sale') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($equipmentType->sold_equipments as $soldEquipment) : ?>
                        <tr style="<?= $soldEquipment->style ?>">
                            <td><?= $soldEquipment->__isset('customer') ?
                                $this->Html->link(
                                    $soldEquipment->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $soldEquipment->customer->id]
                                ) : '' ?></td>
                            <td><?= $soldEquipment->__isset('customer') ?
                                h($soldEquipment->customer->number) : '' ?></td>
                            <td><?= $soldEquipment->__isset('contract') ?
                                $this->Html->link(
                                    $soldEquipment->contract->number ?? '--',
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $soldEquipment->contract->id,
                                        'customer_id' => $soldEquipment->contract->customer_id,
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
                                    ['controller' => 'SoldEquipments', 'action' => 'delete', $soldEquipment->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
