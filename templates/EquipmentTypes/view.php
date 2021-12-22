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
            <?= $this->AuthLink->link(__('Edit Equipment Type'), ['action' => 'edit', $equipmentType->id], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->postLink(__('Delete Equipment Type'), ['action' => 'delete', $equipmentType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $equipmentType->id), 'class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('List Equipment Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Equipment Type'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="equipmentTypes view content">
            <h3><?= h($equipmentType->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($equipmentType->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($equipmentType->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Price') ?></th>
                    <td><?= $this->Number->format($equipmentType->price) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Borrowed Equipments') ?></h4>
                <?php if (!empty($equipmentType->borrowed_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Equipment Type Id') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($equipmentType->borrowed_equipments as $borrowedEquipment) : ?>
                        <tr>
                            <td><?= h($borrowedEquipment->id) ?></td>
                            <td><?= h($borrowedEquipment->customer_id) ?></td>
                            <td><?= h($borrowedEquipment->contract_id) ?></td>
                            <td><?= h($borrowedEquipment->equipment_type_id) ?></td>
                            <td><?= h($borrowedEquipment->serial_number) ?></td>
                            <td><?= h($borrowedEquipment->created) ?></td>
                            <td><?= h($borrowedEquipment->created_by) ?></td>
                            <td><?= h($borrowedEquipment->modified) ?></td>
                            <td><?= h($borrowedEquipment->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(__('View'), ['controller' => 'BorrowedEquipments', 'action' => 'view', $borrowedEquipment->id]) ?>
                                <?= $this->AuthLink->link(__('Edit'), ['controller' => 'BorrowedEquipments', 'action' => 'edit', $borrowedEquipment->id]) ?>
                                <?= $this->AuthLink->postLink(__('Delete'), ['controller' => 'BorrowedEquipments', 'action' => 'delete', $borrowedEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipment->id)]) ?>
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
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Equipment Type Id') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($equipmentType->sold_equipments as $soldEquipment) : ?>
                        <tr>
                            <td><?= h($soldEquipment->id) ?></td>
                            <td><?= h($soldEquipment->customer_id) ?></td>
                            <td><?= h($soldEquipment->contract_id) ?></td>
                            <td><?= h($soldEquipment->equipment_type_id) ?></td>
                            <td><?= h($soldEquipment->serial_number) ?></td>
                            <td><?= h($soldEquipment->created) ?></td>
                            <td><?= h($soldEquipment->created_by) ?></td>
                            <td><?= h($soldEquipment->modified) ?></td>
                            <td><?= h($soldEquipment->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(__('View'), ['controller' => 'SoldEquipments', 'action' => 'view', $soldEquipment->id]) ?>
                                <?= $this->AuthLink->link(__('Edit'), ['controller' => 'SoldEquipments', 'action' => 'edit', $soldEquipment->id]) ?>
                                <?= $this->AuthLink->postLink(__('Delete'), ['controller' => 'SoldEquipments', 'action' => 'delete', $soldEquipment->id], ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipment->id)]) ?>
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
