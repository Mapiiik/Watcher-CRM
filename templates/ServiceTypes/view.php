<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ServiceType $serviceType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Service Type'),
                ['action' => 'edit', $serviceType->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Service Type'),
                ['action' => 'delete', $serviceType->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $serviceType->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(__('List Service Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Service Type'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="serviceTypes view content">
            <h3><?= h($serviceType->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($serviceType->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract Number Format') ?></th>
                            <td><?= h($serviceType->contract_number_format) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Subscriber Verification Code Format') ?></th>
                            <td><?= h($serviceType->subscriber_verification_code_format) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee') ?></th>
                            <td><?= h($serviceType->activation_fee) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Activation Fee With Obligation') ?></th>
                            <td><?= h($serviceType->activation_fee_with_obligation) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice Text') ?></th>
                            <td><?= h($serviceType->invoice_text) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Separate Invoice') ?></th>
                            <td><?= $serviceType->separate_invoice ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Invoice With Items') ?></th>
                            <td><?= $serviceType->invoice_with_items ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Installation Address Required') ?></th>
                            <td><?= $serviceType->installation_address_required ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Access Point Required') ?></th>
                            <td><?= $serviceType->access_point_required ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Normally With Borrowed Equipment') ?></th>
                            <td><?= $serviceType->normally_with_borrowed_equipment ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Have Contract Versions') ?></th>
                            <td><?= $serviceType->have_contract_versions ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Have Equipments') ?></th>
                            <td><?= $serviceType->have_equipments ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Have IP Addresses') ?></th>
                            <td><?= $serviceType->have_ip_addresses ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Have RADIUS Accounts') ?></th>
                            <td><?= $serviceType->have_radius_accounts ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($serviceType->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($serviceType->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $serviceType->__isset('creator') ? $this->Html->link(
                                $serviceType->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $serviceType->creator->id,
                                ]
                            ) : h($serviceType->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($serviceType->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $serviceType->__isset('modifier') ? $this->Html->link(
                                $serviceType->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $serviceType->modifier->id,
                                ]
                            ) : h($serviceType->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Services') ?></h4>
                <?php if (!empty($serviceType->services)) : ?>
                <div class="table-responsive">
                <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <th><?= __('Price') ?></th>
                            <th><?= __('Queue') ?></th>
                            <th><?= __('Not For New Customers') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($serviceType->services as $service) : ?>
                        <tr>
                            <td><?= h($service->name) ?></td>
                            <td><?= $this->Number->currency($service->price) ?></td>
                            <td>
                                <?= $service->__isset('queue') ? $this->Html->link(
                                    $service->queue->name,
                                    ['controller' => 'Queues', 'action' => 'view', $service->queue->id]
                                ) : '' ?>
                            </td>
                            <td><?= $service->not_for_new_customers ? __('Yes') : __('No'); ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Services', 'action' => 'view', $service->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Services', 'action' => 'edit', $service->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Services', 'action' => 'delete', $service->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $service->id)]
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Contracts') ?></h4>
                <?php if (!empty($serviceType->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Contract State') ?></th>
                            <th><?= __('Installation Address') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <th><?= __('Installation Technician') ?></th>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <th><?= __('Commission') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($serviceType->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?=
                                $contract->__isset('customer') ? $this->Html->link(
                                    $contract->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                                ) : '' ?></td>
                            <td><?= $contract->__isset('customer') ? h($contract->customer->number) : '' ?></td>
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->__isset('contract_state') ? $this->Html->link(
                                    $contract->contract_state->name,
                                    [
                                        'controller' => 'ContractStates',
                                        'action' => 'view',
                                        $contract->contract_state->id,
                                    ]
                                ) : '' ?></td>
                            <td><?=
                                $contract->__isset('installation_address') ? $this->Html->link(
                                    $contract->installation_address->full_address,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->installation_address->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->__isset('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?=
                                $contract->__isset('installation_technician') ? $this->Html->link(
                                    $contract->installation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->installation_technician->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?=
                                $contract->__isset('uninstallation_technician') ? $this->Html->link(
                                    $contract->uninstallation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->uninstallation_technician->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <td><?=
                                $contract->__isset('commission') ? $this->Html->link(
                                    $contract->commission->name,
                                    ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id]
                                ) : '' ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Contracts', 'action' => 'view', $contract->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Contracts', 'action' => 'edit', $contract->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Contracts', 'action' => 'delete', $contract->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)]
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
