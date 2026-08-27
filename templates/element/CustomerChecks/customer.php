<?php
/**
 * The customer, with what is on file to reach them by.
 *
 * Shared by the checks whose finding is the customer rather than anything hanging off them.
 * They differ in what they are looking for, not in what there is to show afterwards.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Customer> $records
 * @var bool|null $customer_column
 */

$customer_column ??= true;
?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($customer_column) : ?>
                    <th><?= __('Customer') ?></th>
                    <th><?= __('Customer Number') ?></th>
                <?php endif ?>
                <th><?= __('Identity Number') ?></th>
                <th><?= __('Date of Birth') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $customer) : ?>
                <tr>
                    <?php if ($customer_column) : ?>
                        <td class="dashboard-wrap">
                            <?= $this->Html->link(
                                $customer->name_for_lists,
                                ['controller' => 'Customers', 'action' => 'view', $customer->id],
                            ) ?>
                        </td>
                        <td><?= h($customer->number) ?></td>
                    <?php endif ?>
                    <td><?= h($customer->identity_number) ?></td>
                    <td><?= h($customer->date_of_birth) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
