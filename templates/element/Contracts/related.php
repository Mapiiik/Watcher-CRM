<?php
/**
 * The contracts filed under a contract state, a service type or a commission, as they are listed
 * beside it.
 *
 * Whichever of the three the card is about is left out of the table: it would say the same thing
 * on every row.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $contracts
 * @var bool|null $contract_state_column
 * @var bool|null $service_type_column
 * @var bool|null $commission_column
 */
?>
<?php if (!empty($contracts)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __('Customer') ?></th>
            <th><?= __('Customer Number') ?></th>
            <th><?= __('Number') ?></th>
            <?php if (!empty($contract_state_column)) : ?>
            <th><?= __('Contract State') ?></th>
            <?php endif; ?>
            <?php if (!empty($service_type_column)) : ?>
            <th><?= __('Service Type') ?></th>
            <?php endif; ?>
            <th><?= __('Installation Address') ?></th>
            <th><?= __('Vip') ?></th>
            <th><?= __('Access Point') ?></th>
            <th><?= __('Installation/Establishment Date') ?></th>
            <th><?= __('Installation Technician') ?></th>
            <th><?= __('Uninstallation/Cancellation Date') ?></th>
            <th><?= __('Uninstallation Technician') ?></th>
            <th><?= __('Date of Termination of Services') ?></th>
            <?php if (!empty($commission_column)) : ?>
            <th><?= __('Commission') ?></th>
            <?php endif; ?>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($contracts as $contract) : ?>
        <tr style="<?= $contract->style ?>">
            <td><?=
                $contract->customer !== null ? $this->Html->link(
                    $contract->customer->name ?? '(' . $contract->customer->id . ')',
                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                ) : '' ?></td>
            <td><?= $contract->customer !== null ? h($contract->customer->number) : '' ?></td>
            <td><?= h($contract->number) ?></td>
            <?php if (!empty($contract_state_column)) : ?>
            <td><?=
                $contract->contract_state !== null ? $this->Html->link(
                    $contract->contract_state->name ?? '(' . $contract->contract_state->id . ')',
                    ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id],
                ) : '' ?></td>
            <?php endif; ?>
            <?php if (!empty($service_type_column)) : ?>
            <td><?=
                $contract->service_type !== null ? $this->Html->link(
                    $contract->service_type->name ?? '(' . $contract->service_type->id . ')',
                    ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id],
                ) : '' ?></td>
            <?php endif; ?>
            <td><?=
                $contract->installation_address !== null ? $this->Html->link(
                    $contract->installation_address->full_address,
                    ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id],
                ) : '' ?></td>
            <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
            <td><?= $this->element('AccessPoints/link', [
                'id' => $contract->access_point_id,
                'name' => $contract->access_point->data?->name,
                'answer' => $contract->access_point,
                ]) ?></td>
            <td><?= h($contract->installation_date) ?></td>
            <td><?=
                $contract->installation_technician !== null ? $this->Html->link(
                    $contract->installation_technician->name
                    ?? '(' . $contract->installation_technician->id . ')',
                    ['controller' => 'Customers', 'action' => 'view', $contract->installation_technician->id],
                ) : '' ?></td>
            <td><?= h($contract->uninstallation_date) ?></td>
            <td><?=
                $contract->uninstallation_technician !== null ? $this->Html->link(
                    $contract->uninstallation_technician->name
                    ?? '(' . $contract->uninstallation_technician->id . ')',
                    [
                        'controller' => 'Customers',
                        'action' => 'view',
                        $contract->uninstallation_technician->id,
                    ],
                ) : '' ?></td>
            <td><?= h($contract->termination_date) ?></td>
            <?php if (!empty($commission_column)) : ?>
            <td><?=
                $contract->commission !== null ? $this->Html->link(
                    $contract->commission->name ?? '(' . $contract->commission->id . ')',
                    ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id],
                ) : '' ?></td>
            <?php endif; ?>
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
