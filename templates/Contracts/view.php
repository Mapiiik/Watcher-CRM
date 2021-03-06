<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var string[]|\Cake\Collection\CollectionInterface $ip_address_types_of_use
 * @var string[]|\Cake\Collection\CollectionInterface $ip_network_types_of_use
 */

use Cake\I18n\Number;
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Contract'),
                ['action' => 'edit', $contract->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Contract'),
                ['action' => 'delete', $contract->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Contracts'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Contract'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
            <br />
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $contract->id],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
        <hr>
        <div class="side-labels">
            <h4 class="heading"><?= __('Labels') ?></h4>
            <?php foreach ($contract->customer->customer_labels as $customer_label) : ?>
                <?= $this->Html->link(
                    $customer_label->label->name,
                    ['controller' => 'CustomerLabels', 'action' => 'view', $customer_label->id],
                    [
                        'class' => 'app-label win-link',
                        'title' => $customer_label->label->caption . PHP_EOL
                            . $customer_label->created . PHP_EOL
                            . $customer_label->note,
                        'style' => 'background-color: ' . $customer_label->label->color . ';',
                    ]
                ) ?>
            <?php endforeach ?>
        </div>
        <div class="side-nav">
            <?= $this->AuthLink->link(
                __('New Customer Label'),
                ['controller' => 'CustomerLabels', 'action' => 'add'],
                ['class' => 'side-nav-item win-link']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contracts view content">
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $contract->id],
                ['class' => 'button float-right']
            ) ?>
            <h3><?= h($contract->number) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->has('customer') ? $this->Html->link(
                                $contract->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $contract->has('service_type') ? $this->Html->link(
                                $contract->service_type->name,
                                ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($contract->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address') ?></th>
                            <td><?= $contract->has('installation_address') ? $this->Html->link(
                                $contract->installation_address->full_address,
                                ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]
                            ) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Access Point') ?></th>
                            <td><?= $contract->has('access_point') ? h($contract->access_point->name) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Date') ?></th>
                            <td><?= h($contract->installation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Technician') ?></th>
                            <td><?= $contract->has('installation_technician') ? $this->Html->link(
                                $contract->installation_technician->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->installation_technician->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Commission') ?></th>
                            <td><?= $contract->has('commission') ? $this->Html->link(
                                $contract->commission->name,
                                ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Vip') ?></th>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee') ?></th>
                            <td><?= h($contract->activation_fee) ?><?= $contract->has('service_type') ?
                                ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?= h($contract->activation_fee_with_obligation) ?><?= $contract->has('service_type') ?
                                ' (' . h($contract->service_type->activation_fee_with_obligation) . ')' : '' ?></td>
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
                            <td style="<?=
                                isset($contract->obligation_until) && $contract->obligation_until->isFuture() ?
                                    'color: red;' : ''
                            ?>"><?= h($contract->obligation_until) ?></td>
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
                            <td><?= $contract->has('creator') ? $this->Html->link(
                                $contract->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $contract->creator->id,
                                ]
                            ) : h($contract->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($contract->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $contract->has('modifier') ? $this->Html->link(
                                $contract->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $contract->modifier->id,
                                ]
                            ) : h($contract->modified_by) ?></td>
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
                <?= $this->AuthLink->link(
                    __('New Billing'),
                    ['controller' => 'Billings', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <?php if ($contract->has('valid_until')) : ?>
                    <?= $this->AuthLink->postLink(
                        __('Terminate Related Billings'),
                        ['action' => 'terminateRelatedBillings', $contract->id],
                        [
                            'confirm' => __(
                                'Are you sure you want to terminate related billings for contract # {0}?',
                                $contract->number
                            ),
                            'class' => 'button button-small float-right',
                        ]
                    ) ?>
                <?php endif ?>
                <h4><?= __('Billings') ?></h4>
                <?php if (!empty($contract->billings)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Service') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Quantity') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Fixed Discount') ?></th>
                            <th><?= __('Percentage Discount') ?></th>
                            <th><?= __('Total Price') ?></th>
                            <th><?= __('Billing From') ?></th>
                            <th><?= __('Billing Until') ?></th>
                            <th><?= __('Active') ?></th>
                            <th><?= __('Separate Invoice') ?></th>
                            <th><?= __('Note') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->billings as $billing) : ?>
                        <tr style="<?= $billing->style ?>">
                            <td><?= $billing->has('service') ? h($billing->service->name) : '' ?></td>
                            <td><?= h($billing->text) ?></td>
                            <td><?= h($billing->quantity) ?></td>
                            <td><?= h($billing->price) ?><?= $billing->has('service') ?
                                ' (' . h($billing->service->price) . ')' : '' ?></td>
                            <td><?= h($billing->fixed_discount) ?></td>
                            <td><?= h($billing->percentage_discount) ?></td>
                            <td><?= Number::currency($billing->total_price) ?></td>
                            <td><?= h($billing->billing_from) ?></td>
                            <td><?= h($billing->billing_until) ?></td>
                            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                            <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
                            <td><?= h($billing->note) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Billings', 'action' => 'view', $billing->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Billings', 'action' => 'edit', $billing->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Billings', 'action' => 'delete', $billing->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Ip'),
                    ['controller' => 'Ips', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __('Ips') ?></h4>
                <?php if (!empty($contract->ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Type Of Use') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Device') ?></th>
                            <th><?= __('Range') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->ips as $ip) : ?>
                        <tr style="<?= $ip->style ?>">
                            <td><?= h($ip->ip) ?></td>
                            <td><?= h($ip_address_types_of_use[$ip->type_of_use]) ?></td>
                            <td><?= h($ip->note) ?></td>
                            <td><?php
                            if (isset($ip->routeros_devices)) {
                                $device = $ip->routeros_devices->first();
                                echo isset($device['id']) ?
                                    $this->Html->link(
                                        $device['system_description'],
                                        env('WATCHER_NMS_URL') . '/routeros-devices/view/' . $device['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                unset($device);
                            }
                            ?></td>
                            <td><?php
                            if (isset($ip->ip_address_ranges)) {
                                $range = $ip->ip_address_ranges->first();
                                echo isset($range['access_point']['id']) ?
                                    __('Access Point') . ': ' . $this->Html->link(
                                        $range['access_point']['name'],
                                        env('WATCHER_NMS_URL') . '/access-points/view/' . $range['access_point']['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                echo isset($range['id']) ?
                                    __('Range') . ': ' . $this->Html->link(
                                        $range['name'],
                                        env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                unset($range);
                            }
                            ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Ips', 'action' => 'view', $ip->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Ips', 'action' => 'edit', $ip->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Ips', 'action' => 'delete', $ip->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $ip->ip)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New IP Network'),
                    ['controller' => 'IpNetworks', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
            <h4><?= __('IP Networks') ?></h4>
                <?php if (!empty($contract->ip_networks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip Network') ?></th>
                            <th><?= __('Type Of Use') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Range') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->ip_networks as $ipNetwork) : ?>
                        <tr style="<?= $ipNetwork->style ?>">
                            <td><?= h($ipNetwork->ip_network) ?></td>
                            <td><?= h($ip_network_types_of_use[$ipNetwork->type_of_use]) ?></td>
                            <td><?= h($ipNetwork->note) ?></td>
                            <td><?php
                            if (isset($ipNetwork->ip_address_ranges)) {
                                $range = $ipNetwork->ip_address_ranges->first();
                                echo isset($range['access_point']['id']) ?
                                    __('Access Point') . ': ' . $this->Html->link(
                                        $range['access_point']['name'],
                                        env('WATCHER_NMS_URL') . '/access-points/view/' . $range['access_point']['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                echo isset($range['id']) ?
                                    __('Range') . ': ' . $this->Html->link(
                                        $range['name'],
                                        env('WATCHER_NMS_URL') . '/ip-address-ranges/view/' . $range['id'],
                                        ['target' => '_blank']
                                    ) . '<br>' : '';
                                unset($range);
                            }
                            ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'IpNetworks', 'action' => 'view', $ipNetwork->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'IpNetworks', 'action' => 'edit', $ipNetwork->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'IpNetworks', 'action' => 'delete', $ipNetwork->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $ipNetwork->ip_network)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New RADIUS Account'),
                    ['plugin' => 'Radius', 'controller' => 'Accounts', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __('RADIUS Accounts') ?></h4>
                <?= $this->cell(
                    'Radius.Accounts',
                    [['Accounts.contract_id' => $contract->id]],
                    ['show_contracts' => false]
                ) ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Borrowed Equipment'),
                    ['controller' => 'BorrowedEquipments', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
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
                        <?php foreach ($contract->borrowed_equipments as $borrowedEquipment) : ?>
                        <tr style="<?= $borrowedEquipment->style ?>">
                            <td><?= h($borrowedEquipment->equipment_type->name) ?></td>
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
                <?= $this->AuthLink->link(
                    __('New Sold Equipment'),
                    ['controller' => 'SoldEquipments', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __('Sold Equipments') ?></h4>
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
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Removed Ip'),
                    ['controller' => 'RemovedIps', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __('Removed Ips') ?></h4>
                <?php if (!empty($contract->removed_ips)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Type Of Use') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->removed_ips as $removedIp) : ?>
                        <tr style="<?= $removedIp->style ?>">
                            <td><?= h($removedIp->ip) ?></td>
                            <td><?= h($ip_address_types_of_use[$removedIp->type_of_use]) ?></td>
                            <td><?= h($removedIp->note) ?></td>
                            <td><?= h($removedIp->removed) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'RemovedIps', 'action' => 'view', $removedIp->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'RemovedIps', 'action' => 'edit', $removedIp->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'RemovedIps', 'action' => 'delete', $removedIp->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Removed IP Network'),
                    ['controller' => 'RemovedIpNetworks', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4><?= __('Removed IP Networks') ?></h4>
                <?php if (!empty($contract->removed_ip_networks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Ip') ?></th>
                            <th><?= __('Type Of Use') ?></th>
                            <th><?= __('Note') ?></th>
                            <th><?= __('Removed') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->removed_ip_networks as $removedIpNetork) : ?>
                        <tr style="<?= $removedIpNetork->style ?>">
                            <td><?= h($removedIpNetork->ip_network) ?></td>
                            <td><?= h($ip_network_types_of_use[$removedIpNetork->type_of_use]) ?></td>
                            <td><?= h($removedIpNetork->note) ?></td>
                            <td><?= h($removedIpNetork->removed) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'RemovedIpNetworks', 'action' => 'view', $removedIpNetork->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'RemovedIpNetworks', 'action' => 'edit', $removedIpNetork->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'RemovedIpNetworks', 'action' => 'delete', $removedIpNetork->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetork->id)]
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
