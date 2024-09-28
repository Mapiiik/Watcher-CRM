<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Commission $commission
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Commission'),
                ['action' => 'edit', $commission->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Commission'),
                ['action' => 'delete', $commission->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $commission->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Commissions'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Commission'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="commissions view content">
            <h3><?= h($commission->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($commission->name) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($commission->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($commission->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $commission->__isset('creator') ? $this->Html->link(
                                $commission->creator->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $commission->creator->id,
                                ]
                            ) : h($commission->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($commission->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $commission->__isset('modifier') ? $this->Html->link(
                                $commission->modifier->username,
                                [
                                    'controller' => 'AppUsers',
                                    'action' => 'view',
                                    $commission->modifier->id,
                                ]
                            ) : h($commission->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Dealer Commissions') ?></h4>
                <?php if (!empty($commission->dealer_commissions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <th><?= __('Fixed') ?></th>
                            <th><?= __('Percentage') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($commission->dealer_commissions as $dealerCommission) : ?>
                        <tr>
                            <td>
                                <?= $dealerCommission->__isset('dealer') ? $this->Html->link(
                                    $dealerCommission->dealer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $dealerCommission->dealer->id]
                                ) : '' ?>
                            </td>
                            <td><?= $dealerCommission->fixed === null ?
                                '' : $this->Number->format($dealerCommission->fixed) ?></td>
                            <td><?= $dealerCommission->percentage === null ?
                                '' : $this->Number->format($dealerCommission->percentage) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'DealerCommissions', 'action' => 'view', $dealerCommission->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'DealerCommissions', 'action' => 'edit', $dealerCommission->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'DealerCommissions', 'action' => 'delete', $dealerCommission->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $dealerCommission->id)]
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
                <?php if (!empty($commission->contracts)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Contract State') ?></th>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Installation Address') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Installation/Establishment Date') ?></th>
                            <th><?= __('Installation Technician') ?></th>
                            <th><?= __('Uninstallation/Cancellation Date') ?></th>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <th><?= __('Date of Termination of Services') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($commission->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td>
                                <?= $contract->__isset('customer') ? $this->Html->link(
                                    $contract->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                                ) : '' ?>
                            </td>
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
                            <td>
                                <?= $contract->__isset('service_type') ? $this->Html->link(
                                    $contract->service_type->name,
                                    ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]
                                ) : '' ?>
                            </td>
                            <td>
                                <?= $contract->__isset('installation_address') ? $this->Html->link(
                                    $contract->installation_address->full_address,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->installation_address->id,
                                    ]
                                ) : '' ?>
                            </td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->__isset('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td>
                                <?= $contract->__isset('installation_technician') ? $this->Html->link(
                                    $contract->installation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->installation_technician->id,
                                    ]
                                ) : '' ?>
                            </td>
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
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    [
                                        'controller' => 'Contracts',
                                        'action' => 'view',
                                        $contract->id,
                                        'customer_id' => $contract->customer_id,
                                    ]
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
