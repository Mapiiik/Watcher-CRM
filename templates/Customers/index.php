<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer[]|\Cake\Collection\CollectionInterface $customers
 * @var bool $is_admin
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column-responsive">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
        <?= $is_admin ? $this->Form->control('advanced_search', [
            'label' => __('Advanced Search'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) : '' ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="customers index content">
    <?= $this->AuthLink->link(__('New Customer'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Customers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id', __('Number')) ?></th>
                    <th><?= $this->Paginator->sort('dealer') ?></th>
                    <th><?= $this->Paginator->sort('tax_rate_id') ?></th>
                    <th><?= $this->Paginator->sort('company') ?></th>
                    <th><?= $this->Paginator->sort('title') ?></th>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('suffix') ?></th>
                    <th><?= __('Contracts') ?></th>
                    <th><?= __('Ips') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer) : ?>
                <tr>
                    <td><?= h($customer->number) ?></td>
                    <td><?= $customer->dealer ? __('Yes') : __('No'); ?></td>
                    <td>
                        <?= $customer->has('tax_rate') ? $this->Html->link(
                            $customer->tax_rate->name,
                            ['controller' => 'TaxRates', 'action' => 'view', $customer->tax_rate->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($customer->company) ?></td>
                    <td><?= h($customer->title) ?></td>
                    <td><?= h($customer->first_name) ?></td>
                    <td><?= h($customer->last_name) ?></td>
                    <td><?= h($customer->suffix) ?></td>
                    <td>
                        <?php foreach ($customer->contracts as $contract) {
                            echo h($contract->number) . '<br />';
                        } ?>
                    </td>
                    <td>
                        <?php foreach ($customer->ips as $ip) {
                            echo h($ip->ip) . '<br />';
                        } ?>
                    </td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $customer->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $customer->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $customer->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $customer->id)]
                        ) ?>
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
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
