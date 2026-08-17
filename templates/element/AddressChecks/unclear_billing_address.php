<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Customer> $records
 */
?>
<table>
    <thead>
        <tr>
            <th><?= __('Customer') ?></th>
            <th><?= __('Problem') ?></th>
            <th><?= __('Billing Address') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $customer) : ?>
            <tr>
                <td>
                    <?= $this->Html->link(
                        $customer->name_for_lists,
                        ['controller' => 'Customers', 'action' => 'view', $customer->id, 'customer_id' => false],
                    ) ?>
                </td>
                <td><?= h($customer->billing_address_problem?->label()) ?></td>
                <td class="dashboard-wrap">
                    <?php if ($customer->billing_address_problem?->isMissing()) : ?>
                        <em><?= __('None') ?></em>
                    <?php else : ?>
                        <?= h($customer->billing_address?->full_address) ?>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
