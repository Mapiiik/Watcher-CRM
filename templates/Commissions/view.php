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
    <div class="column-responsive column-90">
        <div class="commissions view content">
            <h3><?= h($commission->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($commission->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($commission->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($commission->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $commission->has('creator') ? $this->Html->link(
                        $commission->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
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
                    <td><?= $commission->has('modifier') ? $this->Html->link(
                        $commission->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $commission->modifier->id,
                        ]
                    ) : h($commission->modified_by) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Dealer Commissions') ?></h4>
                <?php if (!empty($commission->dealer_commissions)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th><?= __('Fixed') ?></th>
                            <th><?= __('Percentage') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($commission->dealer_commissions as $dealerCommission) : ?>
                        <tr>
                            <td><?= $this->Number->format($dealerCommission->id) ?></td>
                            <td>
                                <?= $dealerCommission->has('dealer') ? $this->Html->link(
                                    $dealerCommission->dealer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $dealerCommission->dealer->id]
                                ) : '' ?>
                            </td>
                            <td><?= $this->Number->format($dealerCommission->fixed) ?></td>
                            <td><?= $this->Number->format($dealerCommission->percentage) ?></td>
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
                            <th><?= __('Id') ?></th>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Service Type') ?></th>
                            <th><?= __('Installation Address') ?></th>
                            <th><?= __('Conclusion Date') ?></th>
                            <th><?= __('Number Of Amendments') ?></th>
                            <th><?= __('Valid From') ?></th>
                            <th><?= __('Valid Until') ?></th>
                            <th><?= __('Obligation Until') ?></th>
                            <th><?= __('Vip') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Installation Date') ?></th>
                            <th><?= __('Installation Technician') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($commission->contracts as $contract) : ?>
                        <tr>
                            <td><?= $this->Number->format($contract->id) ?></td>
                            <td>
                                <?= $contract->has('customer') ? $this->Html->link(
                                    $contract->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                                ) : '' ?>
                            </td>
                            <td><?= h($contract->number) ?></td>
                            <td>
                                <?= $contract->has('service_type') ? $this->Html->link(
                                    $contract->service_type->name,
                                    ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id]
                                ) : '' ?>
                            </td>
                            <td>
                                <?= $contract->has('installation_address') ? $this->Html->link(
                                    $contract->installation_address->full_address,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->installation_address->id,
                                    ]
                                ) : '' ?>
                            </td>
                            <td><?= h($contract->conclusion_date) ?></td>
                            <td><?= $this->Number->format($contract->number_of_amendments) ?></td>
                            <td><?= h($contract->valid_from) ?></td>
                            <td><?= h($contract->valid_until) ?></td>
                            <td><?= h($contract->obligation_until) ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->has('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td>
                                <?= $contract->has('installation_technician') ? $this->Html->link(
                                    $contract->installation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->installation_technician->id,
                                    ]
                                ) : '' ?>
                            </td>
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
