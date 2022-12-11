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
            <?= $this->Html->link(
                __('Edit Contract State'),
                ['action' => 'edit', $contractState->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Form->postLink(
                __('Delete Contract State'),
                ['action' => 'delete', $contractState->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $contractState->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->Html->link(
                __('List Contract States'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(
                __('New Contract State'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="contractStates view content">
            <h3><?= h($contractState->name) ?></h3>
            <div class="row">
            <div class="column-responsive">
                <table>
                    <tr>
                        <th><?= __('Name') ?></th>
                        <td><?= h($contractState->name) ?></td>
                    </tr>
                    <tr>
                        <th><?= __('Color') ?></th>
                        <td style="background-color: <?= h($contractState->color) ?>;"><?=
                            h($contractState->color)
                        ?></td>
                    </tr>
                    <tr>
                        <th><?= __('Active') ?></th>
                        <td><?= $contractState->active ? __('Yes') : __('No'); ?></td>
                    </tr>
                    <tr>
                        <th><?= __('Billed') ?></th>
                        <td><?= $contractState->billed ? __('Yes') : __('No'); ?></td>
                    </tr>
                    <tr>
                        <th><?= __('Blocked') ?></th>
                        <td><?= $contractState->blocked ? __('Yes') : __('No'); ?></td>
                    </tr>
                </table>
            </div>
            <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= h($contractState->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($contractState->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $contractState->has('creator') ? $this->Html->link(
                                $contractState->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $contractState->creator->id,
                                ]
                            ) : h($contractState->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($contractState->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $contractState->has('modifier') ? $this->Html->link(
                                $contractState->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $contractState->modifier->id,
                                ]
                            ) : h($contractState->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
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
                            <th><?= __('Installation Date') ?></th>
                            <th><?= __('Installation Technician') ?></th>
                            <th><?= __('Uninstallation Date') ?></th>
                            <th><?= __('Uninstallation Technician') ?></th>
                            <th><?= __('Termination Date') ?></th>
                            <th><?= __('Commission') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($contractState->contracts as $contract) : ?>
                        <tr style="<?= $contract->style ?>">
                            <td><?=
                                $contract->has('customer') ? $this->Html->link(
                                    $contract->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id]
                                ) : '' ?></td>
                            <td><?= $contract->has('customer') ? h($contract->customer->number) : '' ?></td>
                            <td><?= h($contract->number) ?></td>
                            <td><?=
                                $contract->has('service_type') ? $this->Html->link(
                                    $contract->service_type->name,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->service_type->id,
                                    ]
                                ) : '' ?></td>
                            <td><?=
                                $contract->has('installation_address') ? $this->Html->link(
                                    $contract->installation_address->full_address,
                                    [
                                        'controller' => 'Addresses',
                                        'action' => 'view',
                                        $contract->installation_address->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                            <td><?= $contract->has('access_point') ? h($contract->access_point['name']) : '' ?></td>
                            <td><?= h($contract->installation_date) ?></td>
                            <td><?=
                                $contract->has('installation_technician') ? $this->Html->link(
                                    $contract->installation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->installation_technician->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= h($contract->uninstallation_date) ?></td>
                            <td><?=
                                $contract->has('uninstallation_technician') ? $this->Html->link(
                                    $contract->uninstallation_technician->name,
                                    [
                                        'controller' => 'Customers',
                                        'action' => 'view',
                                        $contract->uninstallation_technician->id,
                                    ]
                                ) : '' ?></td>
                            <td><?= h($contract->termination_date) ?></td>
                            <td><?=
                                $contract->has('commission') ? $this->Html->link(
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
