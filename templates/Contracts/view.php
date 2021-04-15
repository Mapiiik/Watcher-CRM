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
            <?= $this->Html->link(__('Edit Contract'), ['action' => 'edit', 'customer_id' => $customer_id, $contract->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Contract'), ['action' => 'delete', 'customer_id' => $customer_id, $contract->id], ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Contracts'), ['action' => 'index', 'customer_id' => $customer_id], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Contract'), ['action' => 'add', 'customer_id' => $customer_id], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="contracts view content">
            <h3><?= h($contract->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Customer') ?></th>
                    <td><?= $contract->has('customer') ? $this->Html->link($contract->customer->name, ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Installation Address') ?></th>
                    <td><?= $contract->has('installation_address') ? $this->Html->link($contract->installation_address->address, ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Number') ?></th>
                    <td><?= h($contract->number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Service Type') ?></th>
                    <td><?= $contract->has('service_type') ? $this->Html->link($contract->service_type->name, ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]) : '' ?></td>
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
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($contract->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $this->Number->format($contract->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $this->Number->format($contract->modified_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Number Of Amendments') ?></th>
                    <td><?= $this->Number->format($contract->number_of_amendments) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($contract->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($contract->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Obligation Until') ?></th>
                    <td><?= h($contract->obligation_until) ?></td>
                </tr>
                <tr>
                    <th><?= __('Installation Date') ?></th>
                    <td><?= h($contract->installation_date) ?></td>
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
                    <th><?= __('Conclusion Date') ?></th>
                    <td><?= h($contract->conclusion_date) ?></td>
                </tr>
                <tr>
                    <th><?= __('Vip') ?></th>
                    <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($contract->note)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Access Description') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($contract->access_description)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Billings') ?></h4>
                <?php if (!empty($contract->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Separate') ?></th>
                            <th><?= __('Service Id') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->billings as $billings) : ?>
                        <tr>
                            <td><?= h($billings->id) ?></td>
                            <td><?= h($billings->customer_id) ?></td>
                            <td><?= h($billings->text) ?></td>
                            <td><?= h($billings->price) ?></td>
                            <td><?= h($billings->billing_from) ?></td>
                            <td><?= h($billings->note) ?></td>
                            <td><?= h($billings->active) ?></td>
                            <td><?= h($billings->modified_by) ?></td>
                            <td><?= h($billings->modified) ?></td>
                            <td><?= h($billings->created_by) ?></td>
                            <td><?= h($billings->created) ?></td>
                            <td><?= h($billings->billing_until) ?></td>
                            <td><?= h($billings->separate) ?></td>
                            <td><?= h($billings->service_id) ?></td>
                            <td><?= h($billings->quantity) ?></td>
                            <td><?= h($billings->contract_id) ?></td>
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
                <h4><?= __('Related Borrowed Equipments') ?></h4>
                <?php if (!empty($contract->borrowed_equipments)) : ?>
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
                        <?php foreach ($contract->borrowed_equipments as $borrowedEquipments) : ?>
                        <tr>
                            <td><?= h($borrowedEquipments->id) ?></td>
                            <td><?= h($borrowedEquipments->customer_id) ?></td>
                            <td><?= h($borrowedEquipments->contract_id) ?></td>
                            <td><?= h($borrowedEquipments->equipment_type_id) ?></td>
                            <td><?= h($borrowedEquipments->serial_number) ?></td>
                            <td><?= h($borrowedEquipments->created) ?></td>
                            <td><?= h($borrowedEquipments->created_by) ?></td>
                            <td><?= h($borrowedEquipments->modified) ?></td>
                            <td><?= h($borrowedEquipments->modified_by) ?></td>
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
                <h4><?= __('Related Ips') ?></h4>
                <?php if (!empty($contract->ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->ips as $ips) : ?>
                        <tr>
                            <td><?= h($ips->ip) ?></td>
                            <td><?= h($ips->customer_id) ?></td>
                            <td><?= h($ips->note) ?></td>
                            <td><?= h($ips->contract_id) ?></td>
                            <td><?= h($ips->id) ?></td>
                            <td><?= h($ips->created) ?></td>
                            <td><?= h($ips->created_by) ?></td>
                            <td><?= h($ips->modified) ?></td>
                            <td><?= h($ips->modified_by) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Ips', 'action' => 'view', $ips->ip]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Ips', 'action' => 'edit', $ips->ip]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Ips', 'action' => 'delete', $ips->ip], ['confirm' => __('Are you sure you want to delete # {0}?', $ips->ip)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Removed Ips') ?></h4>
                <?php if (!empty($contract->removed_ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Removed By') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Contract Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->removed_ips as $removedIps) : ?>
                        <tr>
                            <td><?= h($removedIps->id) ?></td>
                            <td><?= h($removedIps->removed_by) ?></td>
                            <td><?= h($removedIps->removed) ?></td>
                            <td><?= h($removedIps->ip) ?></td>
                            <td><?= h($removedIps->customer_id) ?></td>
                            <td><?= h($removedIps->note) ?></td>
                            <td><?= h($removedIps->contract_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'RemovedIps', 'action' => 'view', $removedIps->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'RemovedIps', 'action' => 'edit', $removedIps->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'RemovedIps', 'action' => 'delete', $removedIps->id], ['confirm' => __('Are you sure you want to delete # {0}?', $removedIps->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Sold Equipments') ?></h4>
                <?php if (!empty($contract->sold_equipments)) : ?>
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
                        <?php foreach ($contract->sold_equipments as $soldEquipments) : ?>
                        <tr>
                            <td><?= h($soldEquipments->id) ?></td>
                            <td><?= h($soldEquipments->customer_id) ?></td>
                            <td><?= h($soldEquipments->contract_id) ?></td>
                            <td><?= h($soldEquipments->equipment_type_id) ?></td>
                            <td><?= h($soldEquipments->serial_number) ?></td>
                            <td><?= h($soldEquipments->created) ?></td>
                            <td><?= h($soldEquipments->created_by) ?></td>
                            <td><?= h($soldEquipments->modified) ?></td>
                            <td><?= h($soldEquipments->modified_by) ?></td>
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
        </div>
    </div>
</div>
