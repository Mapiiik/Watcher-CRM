<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Ip[]|\Cake\Collection\CollectionInterface $ips
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

<div class="ips index content">
    <?= $this->AuthLink->link(__('New IP Address'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('IP Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip', __('IP Address')) ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ips as $ip) : ?>
                <tr style="<?= $ip->style ?>">
                    <td>
                        <?= $ip->has('customer') ? $this->Html->link(
                            $ip->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $ip->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $ip->has('customer') ? h($ip->customer->number) : '' ?></td>
                    <td>
                        <?= $ip->has('contract') ? $this->Html->link(
                            $ip->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $ip->contract->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($ip->ip) ?></td>
                    <td><?= h($types_of_use[$ip->type_of_use]) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $ip->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $ip->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $ip->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $ip->id)]
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
