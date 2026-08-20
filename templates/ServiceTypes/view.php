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
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Service Type'),
                ['action' => 'delete', $serviceType->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $serviceType->id),
                    'class' => 'side-nav-item',
                ],
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
                        <tr>
                            <th><?= __('Assign IP addresses from behind') ?></th>
                            <td><?= $serviceType->assign_ip_addresses_from_behind ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $serviceType]) ?>
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
                            <th><?= __('Criticality Level') ?></th>
                            <th><?= __('Accounting Product Code') ?></th>
                            <th><?= __('Not For New Customers') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($serviceType->services as $service) : ?>
                        <tr>
                            <td><?= h($service->name) ?></td>
                            <td><?= $service->price === null ?
                                '' : $this->Number->currency($service->price->toString()) ?></td>
                            <td>
                                <?= $service->queue !== null ? $this->Html->link(
                                    $service->queue->name ?? '(' . $service->queue->id . ')',
                                    ['controller' => 'Queues', 'action' => 'view', $service->queue->id],
                                ) : '' ?>
                            </td>
                            <td><?= $service->criticality_level === null ?
                                '' : h($service->criticality_level->label()) ?></td>
                            <td><?= h($service->accounting_product_code) ?></td>
                            <td><?= $service->not_for_new_customers ? __('Yes') : __('No'); ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Services', 'action' => 'view', $service->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Services', 'action' => 'edit', $service->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Services', 'action' => 'delete', $service->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $service->id)],
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
                <?= $this->element('Contracts/related', [
                    'contracts' => $serviceType->contracts,
                    'contract_state_column' => true,
                    'commission_column' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>
