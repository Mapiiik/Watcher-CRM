<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Address> $records
 */
?>
<table>
    <thead>
        <tr>
            <th><?= __('Address') ?></th>
            <th><?= __('Customer') ?></th>
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
                <td><?= $address->manual_coordinate_setting ? __('Yes') : __('No') ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
