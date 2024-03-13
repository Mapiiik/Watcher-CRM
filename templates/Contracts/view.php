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
            <br>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $contract->id],
                ['class' => 'side-nav-item']
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('List Customer Messages'),
                ['controller' => 'CustomerMessages', 'action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('List Access Credentials'),
                ['controller' => 'AccessCredentials', 'action' => 'index'],
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
        <?php if (!($this->getRequest()->getQuery('win-link') == 'true')) : ?>
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
                ['action' => 'view', $contract->id, '#' => 'ip_addresses'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Invoices'),
                ['action' => 'view', $contract->id, '#' => 'invoices'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('Tasks'),
                ['action' => 'view', $contract->id, '#' => 'tasks'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
        <?php endif; ?>
    </aside>
    <div class="column column-90">
        <div class="contracts view content">
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                ['action' => 'print', $contract->id],
                ['class' => 'button float-right']
            ) ?>
            <a id="contract"></a>
            <?= __('Contract No.') ?><h3><?= h($contract->number) ?></h3>
            <h5><?=
                ($contract->__isset('service_type') ? $contract->service_type->name : '') .
                ($contract->__isset('installation_address') ? ' - ' . $contract->installation_address->address : '')
            ?></h5>
            <div class="row">
                <div class="column">
                    <table style="<?= $contract->style ?>">
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $contract->__isset('customer') ? $this->Html->link(
                                $contract->customer->name,
                                ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Number') ?></th>
                            <td><?= $contract->__isset('customer') ? h($contract->customer->number) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract State') ?></th>
                            <td><?= $contract->__isset('contract_state') ? $this->Html->link(
                                $contract->contract_state->name,
                                ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Service Type') ?></th>
                            <td><?= $contract->__isset('service_type') ? $this->Html->link(
                                $contract->service_type->name,
                                ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <td><?= h($contract->number) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Subscriber Verification Code') ?></th>
                            <td><?= h($contract->subscriber_verification_code) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address') ?></th>
                            <td><?= $contract->__isset('installation_address') ? $this->Html->link(
                                $contract->installation_address->full_address,
                                ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id]
                            ) . ($contract->installation_address->note ?
                                ' (' . h($contract->installation_address->note) . ')' : ''
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th class="actions"><?= __('Map location') ?></th>
                            <td class="actions">
                                <?php if ($contract->__isset('installation_address')) : ?>
                                    <?php $address = $contract->installation_address ?>
                                    <?= $address->__isset('gps_x') && $address->__isset('gps_y') ?
                                        '' : '<span style="color: red;">' . __('unknown') . '</span>' ?>
                                    <?= $address->__isset('gps_x') && $address->__isset('gps_y') ? $this->Html->link(
                                        __('Google Maps'),
                                        'https://maps.google.com/maps?q='
                                            . h("{$address->gps_y},{$address->gps_x}"),
                                        ['target' => '_blank']
                                    ) : '' ?>
                                    <?= $address->__isset('gps_x') && $address->__isset('gps_y') ? $this->Html->link(
                                        __('Mapy.cz'),
                                        'https://mapy.cz/zakladni?source=coor&id='
                                            . h("{$address->gps_x},{$address->gps_y}"),
                                        ['target' => '_blank']
                                    ) : ''?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Access Point') ?></th>
                            <td><?= $contract->__isset('access_point') ? h($contract->access_point['name']) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Commission') ?></th>
                            <td><?= $contract->__isset('commission') ? $this->Html->link(
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
                            <td><?= h($contract->activation_fee) ?><?= $contract->__isset('service_type') ?
                                ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?=
                                h($contract->activation_fee_with_obligation)
                            ?><?=
                                $contract->__isset('service_type') ?
                                    ' (' . h($contract->service_type->activation_fee_with_obligation) . ')' : '' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <td><?= h($contract->installation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Technician') ?></th>
                            <td><?= $contract->__isset('installation_technician') ? $this->Html->link(
                                $contract->installation_technician->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->installation_technician->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <td><?= h($contract->uninstallation_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <td><?= $contract->__isset('uninstallation_technician') ? $this->Html->link(
                                $contract->uninstallation_technician->name,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->uninstallation_technician->id,
                                ]
                            ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <td><?= h($contract->termination_date) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($contract->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($contract->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $contract->__isset('creator') ? $this->Html->link(
                                $contract->creator->username,
                                [
                                    'controller' => 'AppUsers',
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
                            <td><?= $contract->__isset('modifier') ? $this->Html->link(
                                $contract->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $contract->modifier->id,
                                ]
                            ) : h($contract->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="text">
                        <strong><?= __('Access Description') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->access_description)); ?>
                        </blockquote>
                    </div>
                </div>
                <div class="column">
                    <div class="text">
                        <strong><?= __('Note') ?></strong>
                        <blockquote>
                            <?= $this->Text->autoParagraph(h($contract->note)); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
            <?php if ($contract->__isset('service_type') && $contract->service_type->have_contract_versions) : ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Contract Version'),
                    ['controller' => 'ContractVersions', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <h4 id="contract-versions"><?= __('Contract Versions') ?></h4>
                <?= $this->element('Contracts/ContractVersions', [
                    'contract_versions' => $contract->contract_versions,
                ]) ?>
            </div>
            <?php endif; ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('New Billing'),
                    ['controller' => 'Billings', 'action' => 'add'],
                    ['class' => 'button button-small float-right win-link']
                ) ?>
                <?php if ($contract->__isset('termination_date')) : ?>
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
                <?= $this->element('Contracts/Billings', [
                    'billings' => $contract->billings,
                ]) ?>
            </div>
            <?php if ($contract->__isset('service_type') && $contract->service_type->have_radius_accounts) : ?>
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
            <?php if ($contract->__isset('service_type') && $contract->service_type->have_equipments) : ?>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Borrowed Equipment'),
                            ['controller' => 'BorrowedEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <?php
                        if (
                            $contract->__isset('installation_date')
                            || $contract->__isset('uninstallation_date')
                        ) : ?>
                            <?= $this->AuthLink->postLink(
                                __('Set Dates Automatically'),
                                ['action' => 'setDatesForRelatedBorrowedEquipments', $contract->id],
                                [
                                    'confirm' => __(
                                        'Are you sure you want to set dates'
                                        . ' for related borrowed equipments for contract # {0}?',
                                        $contract->number
                                    ),
                                    'class' => 'button button-small float-right',
                                ]
                            ) ?>
                        <?php endif ?>
                        <h4 id="borrowed-equipments"><?= __('Borrowed Equipments') ?></h4>
                        <?= $this->element('Contracts/BorrowedEquipments', [
                            'borrowed_equipments' => $contract->borrowed_equipments,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Sold Equipment'),
                            ['controller' => 'SoldEquipments', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Sold Equipments') ?></h4>
                        <?= $this->element('Contracts/SoldEquipments', [
                            'sold_equipments' => $contract->sold_equipments,
                        ]) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($contract->__isset('service_type') && $contract->service_type->have_ip_addresses) : ?>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Address'),
                            ['controller' => 'IpAddresses', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <?= $contract->__isset('access_point') ? $this->AuthLink->link(
                            __('New IP Address From Range'),
                            ['controller' => 'IpAddresses', 'action' => 'addFromRange'],
                            ['class' => 'button button-small float-right win-link']
                        ) : '' ?>
                        <h4 id="ip_addresses"><?= __('IP Addresses') ?></h4>
                        <?= $this->element('Contracts/IpAddresses', [
                            'ip_addresses' => $contract->ip_addresses,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New IP Network'),
                            ['controller' => 'IpNetworks', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('IP Networks') ?></h4>
                        <?= $this->element('Contracts/IpNetworks', [
                            'ip_networks' => $contract->ip_networks,
                        ]) ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Address'),
                            ['controller' => 'RemovedIpAddresses', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Removed IP Addresses') ?></h4>
                        <?= $this->element('Contracts/RemovedIpAddresses', [
                            'removed_ip_addresses' => $contract->removed_ip_addresses,
                        ]) ?>
                    </div>
                </div>
                <div class="column">
                    <div class="related">
                        <?= $this->AuthLink->link(
                            __('New Removed IP Network'),
                            ['controller' => 'RemovedIpNetworks', 'action' => 'add'],
                            ['class' => 'button button-small float-right win-link']
                        ) ?>
                        <h4><?= __('Removed IP Networks') ?></h4>
                        <?= $this->element('Contracts/RemovedIpNetworks', [
                            'removed_ip_networks' => $contract->removed_ip_networks,
                        ]) ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <hr />
            <div class="related">
                <?= $this->AuthLink->postLink(
                    __('Unblock Debtor'),
                    [
                        'plugin' => 'BookkeepingPohoda',
                        'controller' => 'Debtors',
                        'action' => 'unblock',
                        $contract->customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to unblock # {0}?', $contract->customer->id),
                    ]
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Block Debtor'),
                    [
                        'plugin' => 'BookkeepingPohoda',
                        'controller' => 'Debtors',
                        'action' => 'block',
                        $contract->customer->id,
                    ],
                    [
                        'class' => 'button button-small float-right',
                        'confirm' => __('Are you sure you want to block # {0}?', $contract->customer->id),
                    ]
                ) ?>
                <h4 id="invoices"><?= __('Invoices') ?></h4>
                <?= $this->cell(
                    'BookkeepingPohoda.Invoices',
                    [['Invoices.customer_id' => $contract->customer->id]],
                    ['show_customers' => false]
                ) ?>
            </div>
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
                            <th><?= __('Number') ?></th>
                            <th><?= __('Task Type') ?></th>
                            <th><?= __('Task State') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contract->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= h($task->number) ?></td>
                            <td><?= $task->__isset('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->__isset('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td style="overflow-wrap: break-word; max-width: 600px;">
                                <?= nl2br($task->text ?? '') ?>
                            </td>
                            <td><?= $task->__isset('dealer') ? h($task->dealer->name) : '' ?></td>
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
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->number)]
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
                            <th><?= __('Number') ?></th>
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
                            <td><?= h($task->number) ?></td>
                            <td><?= $task->__isset('task_type') ? h($task->task_type->name) : '' ?></td>
                            <td><?= $task->__isset('task_state') ? h($task->task_state->name) : '' ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td style="overflow-wrap: break-word; max-width: 600px;">
                                <?= nl2br($task->text ?? '') ?>
                            </td>
                            <td><?=
                                $task->__isset('contract') ? $this->Html->link(
                                    $task->contract->name,
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $task->contract->id,
                                        'customer_id' => $task->contract->customer_id,
                                    ]
                                ) : '' ?>
                            </td>
                            <td><?= $task->__isset('dealer') ? h($task->dealer->name) : '' ?></td>
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
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->number)]
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
