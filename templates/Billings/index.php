<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Billing[]|\Cake\Collection\CollectionInterface $billings
 */

use Cake\I18n\Number;
?>
<div class="billings index content">
    <?= $this->AuthLink->link(__('New Billing'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Billings') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('service_id') ?></th>
                    <th><?= $this->Paginator->sort('text') ?></th>
                    <th><?= $this->Paginator->sort('quantity') ?></th>
                    <th><?= $this->Paginator->sort('price') ?></th>
                    <th><?= $this->Paginator->sort('fixed_discount') ?></th>
                    <th><?= $this->Paginator->sort('percentage_discount') ?></th>
                    <th><?= $this->Paginator->sort('total_price') ?></th>
                    <th><?= $this->Paginator->sort('billing_from') ?></th>
                    <th><?= $this->Paginator->sort('billing_until') ?></th>
                    <th><?= $this->Paginator->sort('active') ?></th>
                    <th><?= $this->Paginator->sort('separate') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($billings as $billing) : ?>
                <tr style="<?= $billing->style ?>">
                    <td><?= $this->Number->format($billing->id) ?></td>
                    <td><?= $billing->has('customer') ? $this->Html->link($billing->customer->name, ['controller' => 'Customers', 'action' => 'view', $billing->customer->id]) : '' ?></td>
                    <td><?= $billing->has('contract') ? $this->Html->link($billing->contract->number, ['controller' => 'Contracts', 'action' => 'view', $billing->contract->id]) : '' ?></td>
                    <td><?= $billing->has('service') ? $this->Html->link($billing->service->name, ['controller' => 'Services', 'action' => 'view', $billing->service->id]) : '' ?></td>
                    <td><?= h($billing->text) ?></td>
                    <td><?= $this->Number->format($billing->quantity) ?></td>
                    <td><?= h($billing->price) ?><?= $billing->has('service') ? ' (' . h($billing->service->price) . ')' : '' ?></td>
                    <td><?= h($billing->fixed_discount) ?></td>
                    <td><?= h($billing->percentage_discount) ?></td>
                    <td><?= Number::currency($billing->total_price) ?></td>
                    <td><?= h($billing->billing_from) ?></td>
                    <td><?= h($billing->billing_until) ?></td>
                    <td><?= $billing->active ? __('Yes') : __('No'); ?></td>
                    <td><?= $billing->separate ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $billing->id]) ?>
                        <?= $this->AuthLink->link(__('Edit'), ['action' => 'edit', $billing->id]) ?>
                        <?= $this->AuthLink->postLink(__('Delete'), ['action' => 'delete', $billing->id], ['confirm' => __('Are you sure you want to delete # {0}?', $billing->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
