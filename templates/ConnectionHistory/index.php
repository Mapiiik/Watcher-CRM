<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ConnectionHistory> $connectionHistory
 * @var string|null $customer_id
 * @var string|null $contract_id
 */

// outside the card of a customer or a contract there is nothing else saying who
// the interval belongs to, so the listing has to carry it itself
$showCustomer = $customer_id === null;
$showContract = $contract_id === null;
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="connectionHistory index content">
    <h3><?= __('Connection History') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('first_seen', __('From')) ?></th>
                    <th><?= $this->Paginator->sort('last_seen', __('Until')) ?></th>
                    <?php if ($showCustomer) : ?>
                    <th><?= $this->Paginator->sort('Customers.company', __('Customer')) ?></th>
                    <?php endif ?>
                    <?php if ($showContract) : ?>
                    <th><?= $this->Paginator->sort('Contracts.number', __('Contract')) ?></th>
                    <?php endif ?>
                    <th><?= $this->Paginator->sort('source_reference', __('Source Reference')) ?></th>
                    <th><?= $this->Paginator->sort('access_point_name', __('Access Point')) ?></th>
                    <th><?= $this->Paginator->sort('routeros_device_name', __('RouterOS Device')) ?></th>
                    <th><?= $this->Paginator->sort('nas_ip_address', __('NAS IP Address')) ?></th>
                    <th><?= $this->Paginator->sort('nas_port_id', __('NAS Port ID')) ?></th>
                    <th><?= $this->Paginator->sort('station_id', __('Calling Station ID')) ?></th>
                    <th><?= $this->Paginator->sort('ip_address', __('Framed IP Address')) ?></th>
                    <th><?= $this->Paginator->sort('ipv6_prefix', __('Framed IPv6 Prefix')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($connectionHistory as $interval) : ?>
                <tr>
                    <td><?= $this->element('ConnectionHistory/first_seen', ['interval' => $interval]) ?></td>
                    <td><?= h($interval->last_seen) ?></td>
                    <?php if ($showCustomer) : ?>
                    <td><?=
                        $interval->hasValue('customer') ? $this->Html->link(
                            $interval->customer->name,
                            [
                                'controller' => 'Customers',
                                'action' => 'view',
                                $interval->customer->id,
                            ],
                        ) : '' ?></td>
                    <?php endif ?>
                    <?php if ($showContract) : ?>
                    <td><?=
                        $interval->hasValue('contract') ? $this->Html->link(
                            $interval->contract->number ?? '--',
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $interval->contract->id,
                                'customer_id' => $interval->contract->customer_id,
                            ],
                        ) : '' ?></td>
                    <?php endif ?>
                    <td><?= $this->element('ConnectionHistory/source_reference', ['interval' => $interval]) ?></td>
                    <td><?= $this->element('ConnectionHistory/access_point', ['interval' => $interval]) ?></td>
                    <td><?= $this->element('ConnectionHistory/routeros_device', ['interval' => $interval]) ?></td>
                    <td><?= h($interval->nas_ip_address) ?></td>
                    <td><?= h($interval->nas_port_id) ?></td>
                    <td><?= h($interval->station_id) ?></td>
                    <td><?= h($interval->ip_address) ?></td>
                    <td><?= h($interval->ipv6_prefix) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $interval->id],
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
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total'),
        ) ?></p>
    </div>
</div>
