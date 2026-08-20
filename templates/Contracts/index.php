<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $contracts
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('contract_state_id', [
            'empty' => true,
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('service_type_id', [
            'empty' => true,
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="contracts index content">
    <?= $this->AuthLink->link(__('New Contract'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Contracts') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('number') ?></th>
                    <th><?= $this->Paginator->sort('contract_state_id') ?></th>
                    <th><?= $this->Paginator->sort('service_type_id') ?></th>
                    <th><?= $this->Paginator->sort('installation_address_id') ?></th>
                    <th><?= $this->Paginator->sort('vip') ?></th>
                    <th><?= $this->Paginator->sort('access_point_id') ?></th>
                    <th><?= $this->Paginator->sort('installation_date', __('Installation/Establishment Date')) ?></th>
                    <th><?= $this->Paginator->sort('installation_technician_id') ?></th>
                    <th><?= $this->Paginator->sort(
                        'uninstallation_date',
                        __('Uninstallation/Cancellation Date'),
                    ) ?></th>
                    <th><?= $this->Paginator->sort('uninstallation_technician_id') ?></th>
                    <th><?= $this->Paginator->sort('termination_date', __('Date of Termination of Services')) ?></th>
                    <th><?= $this->Paginator->sort('commission_id') ?></th>
                    <th><?= $this->Paginator->sort('subscriber_verification_code') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contracts as $contract) : ?>
                <tr style="<?= $contract->style ?>">
                    <td><?=
                        $contract->customer !== null ? $this->Html->link(
                            $contract->customer->name ?? '(' . $contract->customer->id . ')',
                            ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                        ) : '' ?></td>
                    <td><?= $contract->customer !== null ? h($contract->customer->number) : '' ?></td>
                    <td><?= h($contract->number) ?></td>
                    <td><?=
                        $contract->contract_state !== null ? $this->Html->link(
                            $contract->contract_state->name ?? '(' . $contract->contract_state->id . ')',
                            ['controller' => 'ContractStates', 'action' => 'view', $contract->contract_state->id],
                        ) : '' ?></td>
                    <td><?=
                        $contract->service_type !== null ? $this->Html->link(
                            $contract->service_type->name ?? '(' . $contract->service_type->id . ')',
                            ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id],
                        ) : '' ?></td>
                    <td><?=
                        $contract->installation_address !== null ? $this->Html->link(
                            $contract->installation_address->full_address,
                            ['controller' => 'Addresses', 'action' => 'view', $contract->installation_address->id],
                        ) : '' ?></td>
                    <td><?= $contract->vip ? __('Yes') : __('No'); ?></td>
                    <td><?= $this->element('AccessPoints/link', [
                        'id' => $contract->access_point_id,
                        'name' => $contract->access_point_name,
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
                    <td><?=
                        $contract->commission !== null ? $this->Html->link(
                            $contract->commission->name ?? '(' . $contract->commission->id . ')',
                            ['controller' => 'Commissions', 'action' => 'view', $contract->commission->id],
                        ) : '' ?></td>
                    <td><?= h($contract->subscriber_verification_code) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $contract->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $contract->id],
                            ['class' => 'win-link'],
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $contract->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $contract->id)],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
