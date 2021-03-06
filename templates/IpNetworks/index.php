<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\IpNetwork[]|\Cake\Collection\CollectionInterface $ipNetworks
 * @var string[]|\Cake\Collection\CollectionInterface $types_of_use
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
    </div>
</div>
<?= $this->Form->end() ?>

<div class="ipNetworks index content">
    <?= $this->Html->link(__('New Ip Network'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Ip Networks') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip_network') ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ipNetworks as $ipNetwork) : ?>
                <tr>
                    <td><?= $this->Number->format($ipNetwork->id) ?></td>
                    <td><?= $ipNetwork->has('customer') ?
                        $this->Html->link(
                            $ipNetwork->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $ipNetwork->customer->id]
                        ) : '' ?></td>
                    <td><?= $ipNetwork->has('contract') ?
                        $this->Html->link(
                            $ipNetwork->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $ipNetwork->contract->id]
                        ) : '' ?></td>
                    <td><?= h($ipNetwork->ip_network) ?></td>
                    <td><?= h($types_of_use[$ipNetwork->type_of_use]) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $ipNetwork->id]) ?>
                        <?= $this->Html->link(
                            __('Edit'),
                            ['action' => 'edit', $ipNetwork->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->Form->postLink(
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
