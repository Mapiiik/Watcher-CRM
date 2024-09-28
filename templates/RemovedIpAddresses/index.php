<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\RemovedIpAddress> $removedIpAddresses
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

<div class="removedIpAddresses index content">
    <?= $this->AuthLink->link(
        __('New Removed IP Address'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Removed IP Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip_address', __('IP Address')) ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th><?= $this->Paginator->sort('removed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($removedIpAddresses as $removedIpAddress) : ?>
                <tr style="<?= $removedIpAddress->style ?>">
                    <td>
                        <?= $removedIpAddress->__isset('customer') ? $this->Html->link(
                            $removedIpAddress->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $removedIpAddress->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $removedIpAddress->__isset('customer') ? h($removedIpAddress->customer->number) : '' ?></td>
                    <td>
                        <?= $removedIpAddress->__isset('contract') ? $this->Html->link(
                            $removedIpAddress->contract->number ?? '--',
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $removedIpAddress->contract->id,
                                'customer_id' => $removedIpAddress->contract->customer_id,
                            ]
                        ) : '' ?>
                    </td>
                    <td><?= h($removedIpAddress->ip_address) ?></td>
                    <td><?= h($removedIpAddress->type_of_use->label()) ?></td>
                    <td><?= h($removedIpAddress->removed) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $removedIpAddress->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $removedIpAddress->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $removedIpAddress->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $removedIpAddress->id)]
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
