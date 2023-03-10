<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Customer[]|\Cake\Collection\CollectionInterface $customers
 * @var bool $allow_advanced_search
 */
?>
<?= $this->Form->create($filterForm, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
        <?= $allow_advanced_search ? $this->Form->control('advanced_search', [
            'label' => __('Advanced Search'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) : '' ?>
    </div>
    <?php if ($allow_advanced_search) : ?>
    <div class="column">
        <?= $this->Form->control('labels', [
            'multiple' => 'multiple',
            'style' => 'height: 100px;',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <?php endif; ?>
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
                    <th><?= $this->Paginator->sort('company') ?></th>
                    <th><?= $this->Paginator->sort('title') ?></th>
                    <th><?= $this->Paginator->sort('first_name') ?></th>
                    <th><?= $this->Paginator->sort('last_name') ?></th>
                    <th><?= $this->Paginator->sort('suffix') ?></th>
                    <th><?= __('Contracts') ?></th>
                    <th><?= __('IP Addresses') ?></th>
                    <th><?= __('Customer Labels') ?></th>
                    <th><?= $this->Paginator->sort('tax_rate_id') ?></th>
                    <th><?= $this->Paginator->sort('dealer') ?></th>
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
                    <td>
                        <?php foreach ($customer->contracts as $contract) : ?>
                        <span style="<?= $contract->style ?>">
                            <?= $this->Html->link(
                                $contract->number,
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    'customer_id' => $customer->id,
                                    $contract->id,
                                ]
                            ) ?>
                            <?= $contract->has('contract_state') ?
                                '(' . h($contract->contract_state->name) . ')' : '' ?>
                        </span><br />
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach ($customer->ips as $ip) : ?>
                        <span><?= h($ip->ip) ?></span><br />
                        <?php endforeach; ?>
                        <?php foreach ($customer->ip_networks as $ip_network) : ?>
                        <span><?= h($ip_network->ip_network) ?></span><br />
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach ($customer->customer_labels as $customer_label) : ?>
                        <span>
                            <?= $this->Html->link(
                                $customer_label->label->name,
                                ['controller' => 'CustomerLabels', 'action' => 'view', $customer_label->id],
                                [
                                    'class' => 'win-link',
                                    'title' => $customer_label->label->caption . PHP_EOL
                                        . $customer_label->created . PHP_EOL
                                        . $customer_label->note,
                                    'style' => 'color: white; background-color: ' . $customer_label->label->color . ';',
                                ]
                            ) ?>
                        </span><br>
                        <?php endforeach; ?>
                    </td>
                    <td><?= $customer->has('tax_rate') ? h($customer->tax_rate->name) : '' ?></td>
                    <td><?= $customer->getDealerState() ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $customer->id]
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $customer->id],
                            ['class' => 'win-link']
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
