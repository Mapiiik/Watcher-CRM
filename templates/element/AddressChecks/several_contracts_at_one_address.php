<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $records
 */
?>
<p>
    <?= __('Not always a fault. Usually a wrong installation address, picked by mistake or left by an import.') ?>
</p>
<table>
    <thead>
        <tr>
            <th><?= __('Customer') ?></th>
            <th><?= __('Installation Address') ?></th>
            <th><?= __('Contracts') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $group) : ?>
            <tr>
                <td>
                    <?php if ($group->customer !== null) : ?>
                        <?= $this->Html->link(
                            $group->customer->name_for_lists,
                            [
                                'controller' => 'Contracts',
                                'action' => 'index',
                                'customer_id' => $group->customer_id,
                            ],
                        ) ?>
                    <?php endif ?>
                </td>
                <td class="dashboard-wrap">
                    <?php if ($group->installation_address !== null) : ?>
                        <?= $this->Html->link(
                            $group->installation_address->full_address,
                            [
                                'controller' => 'Addresses',
                                'action' => 'view',
                                $group->installation_address_id,
                                'customer_id' => $group->customer_id,
                            ],
                        ) ?>
                    <?php endif ?>
                </td>
                <td><?= h((string)$group->get('total')) ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
