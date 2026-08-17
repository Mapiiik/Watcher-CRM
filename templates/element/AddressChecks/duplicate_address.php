<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Address> $records
 */
?>
<p><?= __('One row per place recorded more than once, not per address.') ?></p>
<table>
    <thead>
        <tr>
            <th><?= __('Customer') ?></th>
            <th><?= __('Type') ?></th>
            <th><?= __('Address') ?></th>
            <th><?= __('Times') ?></th>
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
                                'controller' => 'Addresses',
                                'action' => 'index',
                                'customer_id' => $group->customer_id,
                            ],
                        ) ?>
                    <?php endif ?>
                </td>
                <td><?= h($group->type?->label()) ?></td>
                <td class="dashboard-wrap">
                    <?= h(trim(sprintf('%s %s, %s', $group->street, $group->number, $group->city), ' ,')) ?>
                </td>
                <td><?= h((string)$group->get('total')) ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
