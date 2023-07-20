<?php
use Cake\I18n\Number;

/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Billing> $billings
 * @var bool $contract_column
 */
?>
<?php if (!empty($billings)) : ?>
<div class="table-responsive">
    <table>
        <tr>
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
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
        <?php foreach ($billings as $billing) : ?>
        <tr style="<?= $billing->style ?>">
            <?php if (!empty($contract_column)) : ?>
            <td><?= $billing->__isset('contract') ?
                $this->Html->link(
                    $billing->contract->number,
                    ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]
                ) : '' ?></td>
            <?php endif; ?>
            <td><?= $billing->__isset('service') ? h($billing->service->name) : '' ?></td>
            <td><?= h($billing->text) ?></td>
            <td><?= h($billing->quantity) ?></td>
            <td><?= h($billing->price) ?><?= $billing->__isset('service') ?
                ' (' . h($billing->service->price) . ')' : '' ?></td>
            <td><?= h($billing->fixed_discount) ?></td>
            <td><?= h($billing->percentage_discount) ?></td>
            <td><?= Number::currency($billing->total_price) ?></td>
            <td><?= h($billing->billing_from) ?></td>
            <td><?= h($billing->billing_until) ?></td>
            <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
            <td><?= $billing->separate_invoice ? __('Yes') : __('No'); ?></td>
            <td><?= h($billing->note) ?></td>
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
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
