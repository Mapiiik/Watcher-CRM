<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Contract'), ['action' => 'edit', $contract->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Contract'), ['action' => 'delete', $contract->id], ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Contract'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
            <br />
            <?= $this->Html->link(__('Print to PDF'), ['action' => 'print', $contract->id], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contracts view content">
            <?= $this->Html->link(__('Print to PDF'), ['action' => 'print', $contract->id], ['class' => 'button button float-right']) ?>
            <h3><?= h($contract->number) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->has('customer') ? $this->Html->link($contract->customer->name, ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $contract->has('service_type') ? $this->Html->link($contract->service_type->name, ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($contract->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address') ?></th>
                            <td><?= $contract->has('installation_address') ? $this->Html->link($contract->installation_address->full_address, ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Installation Date') ?></th>
                            <td><?= h($contract->installation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Technician') ?></th>
                            <td><?= $contract->has('installation_technician') ? $this->Html->link($contract->installation_technician->name, ['controller' => 'Customers', 'action' => 'view', $contract->installation_technician->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Brokerage') ?></th>
                            <td><?= $contract->has('brokerage') ? $this->Html->link($contract->brokerage->name, ['controller' => 'Brokerages', 'action' => 'view', $contract->brokerage->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Vip') ?></th>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee') ?></th>
                            <td><?= h($contract->activation_fee) ?><?= $contract->has('service_type') ? ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?= h($contract->activation_fee_with_obligation) ?><?= $contract->has('service_type') ? ' (' . h($contract->service_type->activation_fee_with_obligation) . ')' : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br />
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Conclusion Date') ?></th>
                            <td><?= h($contract->conclusion_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number Of Amendments') ?></th>
                            <td><?= $this->Number->format($contract->number_of_amendments) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid From') ?></th>
                            <td><?= h($contract->valid_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Valid Until') ?></th>
                            <td><?= h($contract->valid_until) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Obligation Until') ?></th>
                            <td><?= h($contract->obligation_until) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($contract->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($contract->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $this->Number->format($contract->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($contract->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $this->Number->format($contract->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Access Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($contract->access_description)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($contract->note)); ?>
                </blockquote>
            </div>
            <div class="related">
                <?= $this->Html->link(__('New Billing'), ['controller' => 'Billings', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Billings') ?></h4>
                <?php if (!empty($contract->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Service') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Fixed Discount') ?></th>
                            <th><?= __('Percentage Discount') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Separate') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->billings as $billings) : ?>
                        <tr>
                            <td><?= $billings->has('service') ? h($billings->service->name) : '' ?></td>
                            <td><?= h($billings->text) ?></td>
                            <td><?= h($billings->quantity) ?></td>
                            <td><?= h($billings->price) ?><?= $billings->has('service') ? ' (' . h($billings->service->price) . ')' : '' ?></td>
                            <td><?= h($billings->billing_from) ?></td>
                            <td><?= h($billings->billing_until) ?></td>
                            <td><?= h($billings->fixed_discount) ?></td>
                            <td><?= h($billings->percentage_discount) ?></td>
                            <td><?= $billings->active ? __('Yes') : __('No'); ?></td>
                            <td><?= $billings->separate ? __('Yes') : __('No'); ?></td>
                            <td><?= h($billings->note) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Billings', 'action' => 'view', $billings->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Billings', 'action' => 'edit', $billings->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Billings', 'action' => 'delete', $billings->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billings->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->Html->link(__('New Borrowed Equipment'), ['controller' => 'BorrowedEquipments', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Borrowed Equipments') ?></h4>
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
                        <?php foreach ($contract->borrowed_equipments as $borrowedEquipments) : ?>
                        <tr>
                            <td><?= h($borrowedEquipments->equipment_type->name) ?></td>
                            <td><?= h($borrowedEquipments->serial_number) ?></td>
                            <td><?= h($borrowedEquipments->borrowed_from) ?></td>
                            <td><?= h($borrowedEquipments->borrowed_until) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'BorrowedEquipments', 'action' => 'view', $borrowedEquipments->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'BorrowedEquipments', 'action' => 'edit', $borrowedEquipments->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'BorrowedEquipments', 'action' => 'delete', $borrowedEquipments->id], ['confirm' => __('Are you sure you want to delete # {0}?', $borrowedEquipments->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->Html->link(__('New Sold Equipment'), ['controller' => 'SoldEquipments', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Sold Equipments') ?></h4>
                <?php if (!empty($contract->sold_equipments)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Equipment Type') ?></th>
                            <th><?= __('Serial Number') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->sold_equipments as $soldEquipments) : ?>
                        <tr>
                            <td><?= h($soldEquipments->equipment_type->name) ?></td>
                            <td><?= h($soldEquipments->serial_number) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'SoldEquipments', 'action' => 'view', $soldEquipments->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'SoldEquipments', 'action' => 'edit', $soldEquipments->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'SoldEquipments', 'action' => 'delete', $soldEquipments->id], ['confirm' => __('Are you sure you want to delete # {0}?', $soldEquipments->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->Html->link(__('New Ip'), ['controller' => 'Ips', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Ips') ?></h4>
                <?php if (!empty($contract->ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->ips as $ip) : ?>
                        <tr>
                            <td><?= h($ip->ip) ?></td>
                            <td><?= h($ip->note) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Ips', 'action' => 'view', $ip->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Ips', 'action' => 'edit', $ip->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Ips', 'action' => 'delete', $ip->id], ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->Html->link(__('New Removed Ip'), ['controller' => 'RemovedIps', 'action' => 'add'], ['class' => 'button button-small float-right']) ?>
                <h4><?= __('Removed Ips') ?></h4>
                <?php if (!empty($contract->removed_ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->removed_ips as $removedIp) : ?>
                        <tr>
                            <td><?= h($removedIp->ip) ?></td>
                            <td><?= h($removedIp->note) ?></td>
                            <td><?= h($removedIp->removed) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'RemovedIps', 'action' => 'view', $removedIp->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'RemovedIps', 'action' => 'edit', $removedIp->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'RemovedIps', 'action' => 'delete', $removedIp->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]) ?>
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
