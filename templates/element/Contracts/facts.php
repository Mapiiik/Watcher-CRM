<?php
/**
 * A contract's own facts, as the pages about it open.
 *
 * Printing and the papers on file both start by saying which contract is being looked at, and
 * they have to say it the same way - what is here is what a contract is, not what either page
 * then does with it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Contract $contract
 * @var bool $showMap Whether to offer the way to the map. The card does, a paper cannot.
 */
?>
<div class="row">
    <div class="column">
        <table style="<?= $contract->style ?>">
            <tr>
                <th><?= __('Customer') ?></th>
                <td><?= $contract->customer !== null ? $this->Html->link(
                    $contract->customer->name ?? '(' . $contract->customer->id . ')',
                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                ) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Customer Number') ?></th>
                <td><?= $contract->customer !== null ? h($contract->customer->number) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Contract State') ?></th>
                <td><?= $contract->contract_state !== null ? $this->Html->link(
                    $contract->contract_state->name ?? '(' . $contract->contract_state->id . ')',
                    ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id],
                ) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Service Type') ?></th>
                <td><?= $contract->service_type !== null ? $this->Html->link(
                    $contract->service_type->name ?? '(' . $contract->service_type->id . ')',
                    ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id],
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
                <td><?= $contract->installation_address !== null ? $this->Html->link(
                    $contract->installation_address->full_address,
                    ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id],
                ) . ($contract->installation_address->note ?
                    ' (' . h($contract->installation_address->note) . ')' : ''
                ) : '' ?></td>
            </tr>
            <?php if ($showMap && $contract->installation_address !== null) : ?>
            <tr>
                <th class="actions"><?= __('Map location') ?></th>
                <td class="actions">
                    <?php $address = $contract->installation_address ?>
                    <?= $address->gps_x !== null && $address->gps_y !== null ?
                        '' : '<span class="error-text">' . __('unknown') . '</span>' ?>
                    <?= $this->element('Maps.Maps/links', [
                        'lat' => $address->gps_y,
                        'lng' => $address->gps_x,
                    ]) ?>
                    <?= $this->AuthLink->link(
                        __('Network Map'),
                        ['action' => 'map', $contract->id],
                        ['class' => 'win-link'],
                    ) ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <div class="column">
        <table>
            <tr>
                <th><?= __('Access Point') ?></th>
                <td><?= $this->element('AccessPoints/link', [
                    'id' => $contract->access_point_id,
                    'name' => $contract->access_point->data?->name,
                    'answer' => $contract->access_point,
                ]) ?></td>
            </tr>
            <tr>
                <th><?= __('Commission') ?></th>
                <td><?= $contract->commission !== null ? $this->Html->link(
                    $contract->commission->name ?? '(' . $contract->commission->id . ')',
                    ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id],
                ) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Vip') ?></th>
                <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
            </tr>
            <tr>
                <th><?= __('Activation Fee') ?></th>
                <td><?= h($contract->activation_fee) ?><?= $contract->service_type !== null ?
                    ' (' . h($contract->service_type->activation_fee) . ')' : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Activation Fee With Obligation') ?></th>
                <td><?=
                    h($contract->activation_fee_with_obligation)
                ?><?=
                    $contract->service_type !== null ?
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
                <td><?= $contract->installation_technician !== null ? $this->Html->link(
                    $contract->installation_technician->name
                    ?? '(' . $contract->installation_technician->id . ')',
                    [
                        'controller' => 'Customers',
                        'action' => 'view',
                        $contract->installation_technician->id,
                    ],
                ) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Uninstallation/Cancellation Date') ?></th>
                <td><?= h($contract->uninstallation_date) ?></td>
            </tr>
            <tr>
                <th><?= __('Uninstallation Technician') ?></th>
                <td><?= $contract->uninstallation_technician !== null ? $this->Html->link(
                    $contract->uninstallation_technician->name
                    ?? '(' . $contract->uninstallation_technician->id . ')',
                    [
                        'controller' => 'Customers',
                        'action' => 'view',
                        $contract->uninstallation_technician->id,
                    ],
                ) : '' ?></td>
            </tr>
            <tr>
                <th><?= __('Date of Termination of Services') ?></th>
                <td><?= h($contract->termination_date) ?></td>
            </tr>
        </table>
    </div>
    <div class="column">
        <?= $this->element('common/audit', ['entity' => $contract]) ?>
    </div>
</div>
