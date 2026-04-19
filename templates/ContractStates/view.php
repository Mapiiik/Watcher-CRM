<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractState $contractState
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Contract State'),
                ['action' => 'edit', $contractState->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Contract State'),
                ['action' => 'delete', $contractState->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractState->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Contract States'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('New Contract State'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractStates view content">
            <h3><?= h($contractState->name) ?></h3>

            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($contractState->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Color') ?></th>
                            <td style="background-color: <?= h($contractState->color) ?>;">
                                <?= h($contractState->color) ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= __('Usable for New Contracts') ?></th>
                            <td><?= $contractState->usable_for_new_contract ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Active Services') ?></th>
                            <td><?= $contractState->active_services ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Billed') ?></th>
                            <td><?= $contractState->billed ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Blocked') ?></th>
                            <td><?= $contractState->blocked ? __('Yes') : __('No') ?></td>
                        </tr>
                    </table>
                </div>

                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Requires Open Task Type') ?></th>
                            <td><?= $contractState->__isset('requires_open_task_type') ? $this->Html->link(
                                $contractState->requires_open_task_type->name,
                                [
                                    'controller' => 'TaskTypes',
                                    'action' => 'view',
                                    $contractState->requires_open_task_type->id,
                                ],
                            ) : __('None') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Open Tasks') ?></th>
                            <td><?= $contractState->requires_no_open_tasks ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Active Billings') ?></th>
                            <td><?= $contractState->requires_no_active_billings ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Future Billings') ?></th>
                            <td><?= $contractState->requires_no_future_billings ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Borrowed Equipments') ?></th>
                            <td><?= $contractState->requires_no_borrowed_equipments ? __('Yes') : __('No') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Requires Installation Date') ?></th>
                            <td><?= $contractState->requires_installation_date ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires Uninstallation Date') ?></th>
                            <td><?= $contractState->requires_uninstallation_date ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires Termination Date') ?></th>
                            <td><?= $contractState->requires_termination_date ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Assigned IP Addresses or Networks') ?></th>
                            <td><?= $contractState->requires_no_assigned_ip_addresses_or_networks ?
                                __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Active RADIUS Accounts') ?></th>
                            <td><?= $contractState->requires_no_active_radius_accounts ? __('Yes') : __('No') ?></td>
                        </tr>
                    </table>
                </div>

                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Requires Contract Version') ?></th>
                            <td><?= $contractState->requires_contract_version ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires Active Contract Version') ?></th>
                            <td><?= $contractState->requires_active_contract_version ? __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires Active or Future Contract Version') ?></th>
                            <td><?= $contractState->requires_active_or_future_contract_version ?
                                __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Active or Future Contract Versions') ?></th>
                            <td><?= $contractState->requires_no_active_or_future_contract_versions ?
                                __('Yes') : __('No') ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Requires No Active Obligations') ?></th>
                            <td><?= $contractState->requires_no_active_obligations ? __('Yes') : __('No') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $contractState]) ?>
                </div>
            </div>

            <div class="text">
                <strong><?= __('Note') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($contractState->note)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Contracts') ?></h4>
                <?php if (!empty($contractState->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Service Type') ?></th>
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
                        <?php foreach ($contractState->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?=
                                $contract->__isset('customer') ? $this->Html->link(
                                    $contract->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                                ) : '' ?></td>
                            <td><?= $contract->__isset('customer') ? h($contract->customer->number) : '' ?></td>
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->__isset('service_type') ? $this->Html->link(
                                    $contract->service_type->name,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->service_type->id,
                                    ],
                                ) : '' ?></td>
                            <td><?=
                                $contract->__isset('installation_address') ? $this->Html->link(
                                    $contract->installation_address->full_address,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->installation_address->id,
                                    ],
                                ) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->__isset('access_point_name') ?
                                h($contract->access_point_name) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?=
                                $contract->__isset('installation_technician') ? $this->Html->link(
                                    $contract->installation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->installation_technician->id,
                                    ],
                                ) : '' ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?=
                                $contract->__isset('uninstallation_technician') ? $this->Html->link(
                                    $contract->uninstallation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->uninstallation_technician->id,
                                    ],
                                ) : '' ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <td><?=
                                $contract->__isset('commission') ? $this->Html->link(
                                    $contract->commission->name,
                                    ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id],
                                ) : '' ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $contract->id,
                                        'customer_id' => $contract->customer_id,
                                    ],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Contracts', 'action' => 'edit', $contract->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Contracts', 'action' => 'delete', $contract->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)],
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
