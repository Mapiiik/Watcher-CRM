<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Customer> $records
 * @var bool|null $customer_column
 */

// on a customer's own page every row is about that customer, so the column says nothing
$customer_column ??= true;
?>
<table>
    <thead>
        <tr>
            <?php if ($customer_column) : ?>
                <th><?= __('Customer') ?></th>
            <?php endif ?>
            <th><?= __('Problem') ?></th>
            <th><?= __('Billing Address') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $customer) : ?>
            <tr>
                <?php if ($customer_column) : ?>
                    <td>
                        <?= $this->Html->link(
                            $customer->name_for_lists,
                            ['controller' => 'Customers', 'action' => 'view', $customer->id, 'customer_id' => false],
                        ) ?>
                    </td>
                <?php endif ?>
                <td><?= h($customer->billing_address_problem?->label()) ?></td>
                <td class="dashboard-wrap">
                    <?php if ($customer->billing_address_problem?->isMissing()) : ?>
                        <em><?= __x('address', 'None') ?></em>
                    <?php else : ?>
                        <?= h($customer->billing_address?->full_address) ?>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
