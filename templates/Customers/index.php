<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer[]|\Cake\Collection\CollectionInterface $customers
 */
?>
<div class="customers index content">
    <?= $this->Html->link(__('New Customer'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Customers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('dealer') ?></th>
                    <th><?= $this->Paginator->sort('title') ?></th>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('suffix') ?></th>
                    <th><?= $this->Paginator->sort('company') ?></th>
                    <th><?= $this->Paginator->sort('taxe_id') ?></th>
                    <th><?= $this->Paginator->sort('bank_name') ?></th>
                    <th><?= $this->Paginator->sort('bank_account') ?></th>
                    <th><?= $this->Paginator->sort('bank_code') ?></th>
                    <th><?= $this->Paginator->sort('modified_by') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th><?= $this->Paginator->sort('created_by') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('ic') ?></th>
                    <th><?= $this->Paginator->sort('dic') ?></th>
                    <th><?= $this->Paginator->sort('www') ?></th>
                    <th><?= $this->Paginator->sort('invoice_delivery') ?></th>
                    <th><?= $this->Paginator->sort('identity_card_number') ?></th>
                    <th><?= $this->Paginator->sort('date_of_birth') ?></th>
                    <th><?= $this->Paginator->sort('termination_date') ?></th>
                    <th><?= $this->Paginator->sort('agree_gdpr') ?></th>
                    <th><?= $this->Paginator->sort('agree_mailing_outages') ?></th>
                    <th><?= $this->Paginator->sort('agree_mailing_commercial') ?></th>
                    <th><?= $this->Paginator->sort('agree_mailing_billing') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?= $this->Number->format($customer->id) ?></td>
                    <td><?= $this->Number->format($customer->dealer) ?></td>
                    <td><?= h($customer->title) ?></td>
                    <td><?= h($customer->first_name) ?></td>
                    <td><?= h($customer->last_name) ?></td>
                    <td><?= h($customer->suffix) ?></td>
                    <td><?= h($customer->company) ?></td>
                    <td><?= $customer->has('tax') ? $this->Html->link($customer->tax->name, ['controller' => 'Taxes', 'action' => 'view', $customer->tax->id]) : '' ?></td>
                    <td><?= h($customer->bank_name) ?></td>
                    <td><?= h($customer->bank_account) ?></td>
                    <td><?= h($customer->bank_code) ?></td>
                    <td><?= $this->Number->format($customer->modified_by) ?></td>
                    <td><?= h($customer->modified) ?></td>
                    <td><?= $this->Number->format($customer->created_by) ?></td>
                    <td><?= h($customer->created) ?></td>
                    <td><?= h($customer->ic) ?></td>
                    <td><?= h($customer->dic) ?></td>
                    <td><?= h($customer->www) ?></td>
                    <td><?= $this->Number->format($customer->invoice_delivery) ?></td>
                    <td><?= h($customer->identity_card_number) ?></td>
                    <td><?= h($customer->date_of_birth) ?></td>
                    <td><?= h($customer->termination_date) ?></td>
                    <td><?= h($customer->agree_gdpr) ?></td>
                    <td><?= h($customer->agree_mailing_outages) ?></td>
                    <td><?= h($customer->agree_mailing_commercial) ?></td>
                    <td><?= h($customer->agree_mailing_billing) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $customer->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $customer->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $customer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id)]) ?>
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
