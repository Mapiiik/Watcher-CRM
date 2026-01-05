<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Customer> $customers
 */
?>
<div class="customers index content">
    <h3><?= __('Customers') . ' - ' . __('Invalid Identification Numbers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Number') ?></th>
                    <th><?= __('Company') ?></th>
                    <th><?= __('Title') ?></th>
                    <th><?= __('First Name') ?></th>
                    <th><?= __('Last Name') ?></th>
                    <th><?= __('Suffix') ?></th>
                    <th><?= __('Identity Number') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer) : ?>
                <tr>
                    <td><?= h($customer->number) ?></td>
                    <td><?= h($customer->company) ?></td>
                    <td><?= h($customer->title) ?></td>
                    <td><?= h($customer->first_name) ?></td>
                    <td><?= h($customer->last_name) ?></td>
                    <td><?= h($customer->suffix) ?></td>
                    <td><?= h($customer->identity_number) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $customer->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $customer->id],
                            ['class' => 'win-link'],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
