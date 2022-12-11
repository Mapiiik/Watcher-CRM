<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\RemovedIp[]|\Cake\Collection\CollectionInterface $removedIps
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

<div class="removedIps index content">
    <?= $this->AuthLink->link(__('New Removed IP Address'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Removed IP Addresses') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id') ?></th>
                    <th><?= $this->Paginator->sort('ip') ?></th>
                    <th><?= $this->Paginator->sort('type_of_use') ?></th>
                    <th><?= $this->Paginator->sort('removed') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($removedIps as $removedIp) : ?>
                <tr style="<?= $removedIp->style ?>">
                    <td>
                        <?= $removedIp->has('customer') ? $this->Html->link(
                            $removedIp->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $removedIp->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $removedIp->has('customer') ? h($removedIp->customer->number) : '' ?></td>
                    <td>
                        <?= $removedIp->has('contract') ? $this->Html->link(
                            $removedIp->contract->number,
                            ['controller' => 'Contracts', 'action' => 'view', $removedIp->contract->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($removedIp->ip) ?></td>
                    <td><?= h($types_of_use[$removedIp->type_of_use]) ?></td>
                    <td><?= h($removedIp->removed) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $removedIp->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $removedIp->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $removedIp->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $removedIp->id)]
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
