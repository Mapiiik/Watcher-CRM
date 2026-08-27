<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Address> $records
 * @var bool|null $customer_column
 */

// on a customer's own page every row is about that customer, so the column says nothing
$customer_column ??= true;
?>
<table>
    <thead>
        <tr>
            <th><?= __('Address') ?></th>
            <?php if ($customer_column) : ?>
                <th><?= __('Customer') ?></th>
            <?php endif ?>
            <th><?= __('Coordinates Set by Hand') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $address) : ?>
            <tr>
                <td class="dashboard-wrap">
                    <?= $this->Html->link(
                        $address->full_address,
                        [
                            'controller' => 'Addresses',
                            'action' => 'view',
                            $address->id,
                            'customer_id' => $address->customer_id,
                        ],
                    ) ?>
                </td>
                <?php if ($customer_column) : ?>
                    <td>
                        <?php if ($address->customer !== null) : ?>
                            <?= $this->Html->link(
                                $address->customer->name_for_lists,
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $address->customer_id,
                                    'customer_id' => false,
                                ],
                            ) ?>
                        <?php endif ?>
                    </td>
                <?php endif ?>
                <td><?= $address->manual_coordinate_setting ? __('Yes') : __('No') ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
