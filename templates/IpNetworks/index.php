<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\IpNetwork> $ipNetworks
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

<div class="ipNetworks index content">
    <?= $this->AuthLink->link(
        __('New IP Network'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('IP Networks') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip_network', __('IP Network')) ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ipNetworks as $ipNetwork) : ?>
                <tr>
                    <td><?= $ipNetwork->__isset('customer') ?
                        $this->Html->link(
                            $ipNetwork->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $ipNetwork->customer->id]
                        ) : '' ?></td>
                    <td><?= $ipNetwork->__isset('customer') ? h($ipNetwork->customer->number) : '' ?></td>
                    <td><?= $ipNetwork->__isset('contract') ?
                        $this->Html->link(
                            $ipNetwork->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $ipNetwork->contract->id]
                        ) : '' ?></td>
                    <td><?= h($ipNetwork->ip_network) ?></td>
                    <td><?= h($ipNetwork->getTypeOfUseName()) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $ipNetwork->id]
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $ipNetwork->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $ipNetwork->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $ipNetwork->id)]
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
