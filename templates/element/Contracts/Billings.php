<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $billings
 * @var bool $customer_column
 * @var bool $contract_column
 * @var bool $disable_actions
 */
?>
<?php if (!empty($billings)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <?php if (!empty($customer_column)) : ?>
            <th><?= __('Customer') ?></th>
            <th><?= __('Customer Number') ?></th>
            <?php endif; ?>
            <?php if (!empty($contract_column)) : ?>
            <th><?= __('Contract') ?></th>
            <?php endif; ?>
            <th><?= __('Service') ?></th>
            <th><?= __('Text') ?></th>
            <th><?= __('Quantity') ?></th>
            <th><?= __('Price') ?></th>
            <th><?= __('Fixed Discount') ?></th>
            <th><?= __('Percentage Discount') ?></th>
            <th><?= __('Total Price') ?></th>
            <th><?= __('Billing From') ?></th>
            <th><?= __('Billing Until') ?></th>
            <th><?= __('Active') ?></th>
            <th><?= __('Separate Invoice') ?></th>
            <th><?= __('Note') ?></th>
            <?php if (empty($disable_actions)) : ?>
            <th class="actions"><?= __('Actions') ?></th>
            <?php endif; ?>
        </tr>
        <?php foreach ($billings as $billing) : ?>
        <tr style="<?= $billing->style ?>">
            <?php if (!empty($customer_column)) : ?>
            <td><?= $billing->__isset('customer') ?
                $this->Html->link(
                    $billing->customer->name,
                    ['controller' => 'Customers', 'action' => 'view', $billing->customer->id]
                ) : '' ?>
            </td>
            <td><?= $billing->__isset('customer') ? h($billing->customer->number) : '' ?></td>
            <?php endif; ?>
            <?php if (!empty($contract_column)) : ?>
            <td><?= $billing->__isset('contract') ?
                $this->Html->link(
                    $billing->contract->number ?? '--',
                    [
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $billing->contract->id,
                        'customer_id' => $billing->contract->customer_id,
                    ]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= $billing->__isset('service') ? h($billing->service->name) : '' ?></td>
            <td><?= h($billing->text) ?></td>
            <td><?= h($billing->quantity) ?></td>
            <td><?= h($billing->price) ?><?= $billing->__isset('service') ?
                ' (' . h($billing->service->price) . ')' : '' ?></td>
            <td><?= h($billing->fixed_discount) ?></td>
            <td><?= h($billing->percentage_discount) ?></td>
            <td><?= $this->Number->currency($billing->total_price->toString()) ?></td>
            <td><?= h($billing->billing_from) ?></td>
            <td><?= h($billing->billing_until) ?></td>
            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
            <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
            <td><?= h($billing->note) ?></td>
            <?php if (empty($disable_actions)) : ?>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'Billings', 'action' => 'view', $billing->id]
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'Billings', 'action' => 'edit', $billing->id],
                    ['class' => 'win-link']
                ) ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'Billings', 'action' => 'delete', $billing->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id)]
                ) ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
