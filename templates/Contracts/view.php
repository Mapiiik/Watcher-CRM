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
        <br>
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
        <div class="side-nav" style="position: fixed; bottom: 1rem;">
            <h4 class="heading"><?= __('Sections') ?></h4>
            <?= $this->AuthLink->link(
                __('Contract'),
                ['action' => 'view', $contract->id, '#' => 'contract'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Contract Versions'),
                ['action' => 'view', $contract->id, '#' => 'contract-versions'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Billings'),
                ['action' => 'view', $contract->id, '#' => 'billings'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('RADIUS Accounts'),
                ['action' => 'view', $contract->id, '#' => 'radius-accounts'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Equipments'),
                ['action' => 'view', $contract->id, '#' => 'borrowed-equipments'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('IP Addresses'),
                ['action' => 'view', $contract->id, '#' => 'ips'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Tasks'),
                ['action' => 'view', $contract->id, '#' => 'tasks'],
                ['class' => 'side-nav-item']
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
            <a id="contract"></a>
            <?= __('Contract No.') ?><h3><?= h($contract->number) ?></h3>
            <h5><?=
                ($contract->has('service_type') ? $contract->service_type->name : '') .
                ($contract->has('installation_address') ? ' - ' . $contract->installation_address->address : '')
            ?></h5>
            <div class="row">
                <div class="column-responsive">
                    <table style="<?= $contract->style ?>">
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->has('customer') ? $this->Html->link(
                                $contract->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $contract->has('customer') ? h($contract->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract State') ?></th>
                            <td><?= $contract->has('contract_state') ? $this->Html->link(
                                $contract->contract_state->name,
                                ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id]
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
                            <td><?= $contract->has('access_point') ? h($contract->access_point['name']) : '' ?></td>
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
                            <th><?= __('Uninstallation Date') ?></th>
                            <td><?= h($contract->uninstallation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <td><?= $contract->has('uninstallation_technician') ? $this->Html->link(
                                $contract->uninstallation_technician->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->uninstallation_technician->id,
                                ]
                            ) : '' ?></td>
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
            <div class="row">
                <div class="column-responsive">
                    <div class="text">
                        <strong><?= __('Access Description') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->access_description)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column-responsive">
                    <div class="text">
                        <strong><?= __('Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->note)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <?php if ($contract->has('service_type') && $contract->service_type->have_contract_versions) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract Version'),
                    ['controller' => 'ContractVersions', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="contract-versions"><?= __('Contract Versions') ?></h4>
                <?php if (!empty($contract->contract_versions)) : ?>
                <div class="table-responsive">
                    <table>
                    <thead>
                        <tr>
                            <th><?= __('Valid From') ?></th>
                            <th><?= __('Valid Until') ?></th>
                            <th><?= __('Obligation Until') ?></th>
                            <th><?= __('Conclusion Date') ?></th>
                            <th><?= __('Number Of Amendments') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contract->contract_versions as $contractVersion) : ?>
                        <tr>
                            <td><?= h($contractVersion->valid_from) ?></td>
                            <td><?= h($contractVersion->valid_until) ?></td>
                            <td style="<?=
                                isset($contractVersion->obligation_until)
                                && $contractVersion->obligation_until->isFuture() ?
                                    'color: red;' : ''
                            ?>"><?= h($contractVersion->obligation_until) ?></td>
                            <td><?= h($contractVersion->conclusion_date) ?></td>
                            <td><?= $this->Number->format($contractVersion->number_of_amendments) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['controller' => 'ContractVersions', 'action' => 'view', $contractVersion->id]
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['controller' => 'ContractVersions', 'action' => 'edit', $contractVersion->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['controller' => 'ContractVersions', 'action' => 'delete', $contractVersion->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contractVersion->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Billing'),
                    ['controller' => 'Billings', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <?php
                if (
                    isset($contract->contract_versions[0])
                    && $contract->contract_versions[0]->has('valid_until')
                ) : ?>
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
                <h4 id="billings"><?= __('Billings') ?></h4>
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
            <?php if ($contract->has('service_type') && $contract->service_type->have_radius_accounts) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New RADIUS Account'),
                    ['plugin' => 'Radius', 'controller' => 'Accounts', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="radius-accounts"><?= __('RADIUS Accounts') ?></h4>
                <?= $this->cell(
                    'Radius.Accounts',
                    [['Accounts.contract_id' => $contract->id]],
                    ['show_contracts' => false]
                ) ?>
            </div>
            <?php endif; ?>
            <?php if ($contract->has('service_type') && $contract->service_type->have_equipments) : ?>
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Borrowed Equipment'),
                            ['controller' => 'BorrowedEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4 id="borrowed-equipments"><?= __('Borrowed Equipments') ?></h4>
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
                    </div>
                </div>
                <div class="column-responsive">
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
                                            [
                                                'controller' => 'SoldEquipments',
                                                'action' => 'delete',
                                                $soldEquipment->id,
                                            ],
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $soldEquipment->id
                                                ),
                                            ]
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
            <?php endif; ?>
            <?php if ($contract->has('service_type') && $contract->service_type->have_ip_addresses) : ?>
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Address'),
                            ['controller' => 'Ips', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <?= $contract->has('access_point') ? $this->AuthLink->link(
                            __('New IP Address From Range'),
                            ['controller' => 'Ips', 'action' => 'addFromRange'],
                            ['class' => 'button button-small float-right win-link']
                        ) : '' ?>
                        <h4 id="ips"><?= __('IP Addresses') ?></h4>
                        <?php if (!empty($contract->ips)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('IP Address') ?></th>
                                    <th><?= __('Type Of Use') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th><?= __('Device') ?></th>
                                    <th><?= __('IP Address Range') ?></th>
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
                                                env('WATCHER_NMS_URL')
                                                    . '/access-points/view/' . $range['access_point']['id'],
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
                </div>
                <div class="column-responsive">
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
                                    <th><?= __('IP Network') ?></th>
                                    <th><?= __('Type Of Use') ?></th>
                                    <th><?= __('Note') ?></th>
                                    <th><?= __('IP Address Range') ?></th>
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
                                                env('WATCHER_NMS_URL')
                                                    . '/access-points/view/' . $range['access_point']['id'],
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
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $ipNetwork->ip_network
                                                ),
                                            ]
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
            <div class="row">
                <div class="column-responsive">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Address'),
                            ['controller' => 'RemovedIps', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Removed IP Addresses') ?></h4>
                        <?php if (!empty($contract->removed_ips)) : ?>
                        <div class="table-responsive">
                            <table>
                                <tr>
                                    <th><?= __('IP Address') ?></th>
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
                </div>
                <div class="column-responsive">
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
                                    <th><?= __('IP Network') ?></th>
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
                                            [
                                                'controller' => 'RemovedIpNetworks',
                                                'action' => 'view',
                                                $removedIpNetork->id,
                                            ]
                                        ) ?>
                                        <?= $this->AuthLink->link(
                                            __('Edit'),
                                            [
                                                'controller' => 'RemovedIpNetworks',
                                                'action' => 'edit',
                                                $removedIpNetork->id,
                                            ],
                                            ['class' => 'win-link']
                                        ) ?>
                                        <?= $this->AuthLink->postLink(
                                            __('Delete'),
                                            [
                                                'controller' => 'RemovedIpNetworks',
                                                'action' => 'delete',
                                                $removedIpNetork->id,
                                            ],
                                            [
                                                'confirm' => __(
                                                    'Are you sure you want to delete # {0}?',
                                                    $removedIpNetork->id
                                                ),
                                            ]
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
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Task'),
                    ['controller' => 'Tasks', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="tasks"><?= __('Tasks') ?></h4>
                <?php if (!empty($contract->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Task Type') ?></th>
                            <th><?= __('Task State') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= $task->has('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->has('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= nl2br($task->text ?? '') ?></td>
                            <td><?= $task->has('dealer') ? h($task->dealer->name) : '' ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Tasks', 'action' => 'view', $task->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Other Customer Tasks') ?></h4>
                <?php if (!empty($contract->customer->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Task Type') ?></th>
                            <th><?= __('Task State') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Contract') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->customer->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= $task->has('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->has('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= nl2br($task->text ?? '') ?></td>
                            <td><?=
                                $task->has('contract') ? $this->Html->link(
                                    $task->contract->name,
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $task->contract->id,
                                        'customer_id' => $task->contract->customer_id,
                                    ]
                                ) : '' ?>
                            </td>
                            <td><?= $task->has('dealer') ? h($task->dealer->name) : '' ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Tasks', 'action' => 'view', $task->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]
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
