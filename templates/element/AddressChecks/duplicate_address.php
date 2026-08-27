<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Address> $records
 * @var bool|null $customer_column
 */

// on a customer's own page every row is about that customer, so the column says nothing
$customer_column ??= true;
?>
<p><?= __('One row per place recorded more than once, not per address.') ?></p>
<table>
    <thead>
        <tr>
            <?php if ($customer_column) : ?>
                <th><?= __('Customer') ?></th>
            <?php endif ?>
            <th><?= __('Type') ?></th>
            <th><?= __('Address') ?></th>
            <th><?= __('Times') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $group) : ?>
            <tr>
                <?php if ($customer_column) : ?>
                    <td>
                        <?php if ($group->customer !== null) : ?>
                            <?= $this->Html->link(
                                $group->customer->name_for_lists,
                                [
                                    'controller' => 'Addresses',
                                    'action' => 'index',
                                    'customer_id' => $group->customer_id,
                                ],
                            ) ?>
                        <?php endif ?>
                    </td>
                <?php endif ?>
                <td><?= h($group->type?->label()) ?></td>
                <td class="dashboard-wrap">
                    <?= h($group->address) ?>
                </td>
                <td><?= h((string)$group->get('total')) ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
