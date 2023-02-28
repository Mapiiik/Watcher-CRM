<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIpNetwork[]|\Cake\Collection\CollectionInterface $removedIpNetworks
 * @var string[]|\Cake\Collection\CollectionInterface $types_of_use
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

<div class="removedIpNetworks index content">
    <?= $this->Html->link(__('New Removed IP Network'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Removed IP Networks') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip_network', __('IP Network')) ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th><?= $this->Paginator->sort('removed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($removedIpNetworks as $removedIpNetwork) : ?>
                <tr>
                    <td><?= $removedIpNetwork->has('customer') ?
                        $this->Html->link(
                            $removedIpNetwork->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $removedIpNetwork->customer->id]
                        ) : '' ?></td>
                    <td><?= $removedIpNetwork->has('customer') ? h($removedIpNetwork->customer->number) : '' ?></td>
                    <td><?= $removedIpNetwork->has('contract') ?
                        $this->Html->link(
                            $removedIpNetwork->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $removedIpNetwork->contract->id]
                        ) : '' ?></td>
                    <td><?= h($removedIpNetwork->ip_network) ?></td>
                    <td><?= h($types_of_use[$removedIpNetwork->type_of_use]) ?></td>
                    <td><?= h($removedIpNetwork->removed) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $removedIpNetwork->id]) ?>
                        <?= $this->Html->link(
                            __('Edit'),
                            ['action' => 'edit', $removedIpNetwork->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
                            __('Delete'),
                            ['action' => 'delete', $removedIpNetwork->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $removedIpNetwork->id)]
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
