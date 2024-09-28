<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\IpAddress> $ipAddresses
 */
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

<div class="ipAddresses index content">
    <?= $this->AuthLink->link(__('New IP Address'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('IP Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip_address', __('IP Address')) ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ipAddresses as $ipAddress) : ?>
                <tr style="<?= $ipAddress->style ?>">
                    <td>
                        <?= $ipAddress->__isset('customer') ? $this->Html->link(
                            $ipAddress->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $ipAddress->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $ipAddress->__isset('customer') ? h($ipAddress->customer->number) : '' ?></td>
                    <td>
                        <?= $ipAddress->__isset('contract') ? $this->Html->link(
                            $ipAddress->contract->number ?? '--',
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $ipAddress->contract->id,
                                'customer_id' => $ipAddress->contract->customer_id,
                            ]
                        ) : '' ?>
                    </td>
                    <td><?= h($ipAddress->ip_address) ?></td>
                    <td><?= h($ipAddress->type_of_use->label()) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $ipAddress->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $ipAddress->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $ipAddress->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $ipAddress->id)]
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
