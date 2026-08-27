<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $records
 * @var bool|null $customer_column
 */

// on a customer's own page every row is about that customer, so the column says nothing
$customer_column ??= true;
?>
<table>
    <thead>
        <tr>
            <th><?= __('Contract') ?></th>
            <?php if ($customer_column) : ?>
                <th><?= __('Customer') ?></th>
            <?php endif ?>
            <th><?= __('Service Type') ?></th>
            <th><?= __('Contract State') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $contract) : ?>
            <tr>
                <td>
                    <?= $this->Html->link(
                        $contract->number ?? (string)$contract->nid,
                        [
                            'controller' => 'Contracts',
                            'action' => 'view',
                            $contract->id,
                            'customer_id' => $contract->customer_id,
                        ],
                    ) ?>
                </td>
                <?php if ($customer_column) : ?>
                    <td>
                        <?php if ($contract->customer !== null) : ?>
                            <?= $this->Html->link(
                                $contract->customer->name_for_lists,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $contract->customer_id,
                                    'customer_id' => false,
                                ],
                            ) ?>
                        <?php endif ?>
                    </td>
                <?php endif ?>
                <td><?= h($contract->service_type?->name) ?></td>
                <td><?= h($contract->contract_state?->name) ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
