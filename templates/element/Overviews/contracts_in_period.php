<?php
/**
 * One of the two listings on the overview of new and ending contracts.
 *
 * The two differ only in which worked-out date they show and what it is called, so the table
 * is written once and told which - the columns beside it are the same question either way:
 * whose contract it is, what it is for, where it runs and what state it is in.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $contracts
 * @var string $date_field The query-only field carrying the day, `starts_on` or `ends_on`.
 * @var string $date_column What that day is called at the head of its column.
 */
?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><?= h($date_column) ?></th>
                <th><?= __('Customer') ?></th>
                <th><?= __('Contract Number') ?></th>
                <th><?= __('Contract State') ?></th>
                <th><?= __('Service Type') ?></th>
                <th><?= __('Installation Address') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contracts as $contract) : ?>
            <tr style="<?= $contract->style ?>">
                <td><?= h($contract->get($date_field)) ?></td>
                <td><?=
                    $contract->customer !== null ? $this->Html->link(
                        $contract->customer->name ?? '(' . $contract->customer->id . ')',
                        ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                    ) : '' ?></td>
                <td><?=
                    $this->Html->link($contract->number ?? '--', [
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $contract->id,
                        'customer_id' => $contract->customer_id,
                    ]) ?></td>
                <td><?=
                    $contract->contract_state !== null ? $this->Html->link(
                        $contract->contract_state->name ?? '(' . $contract->contract_state->id . ')',
                        [
                            'controller' => 'ContractStates',
                            'action' => 'view',
                            $contract->contract_state->id,
                        ],
                    ) : '' ?></td>
                <td><?=
                    $contract->service_type !== null ? $this->Html->link(
                        $contract->service_type->name ?? '(' . $contract->service_type->id . ')',
                        ['controller' => 'ServiceTypes', 'action' => 'view', $contract->service_type->id],
                    ) : '' ?></td>
                <td><?=
                    $contract->installation_address !== null ? $this->Html->link(
                        $contract->installation_address->full_address,
                        [
                            'controller' => 'Addresses',
                            'action' => 'view',
                            $contract->installation_address->id,
                        ],
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
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
