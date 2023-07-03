<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\Phone> $phones
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

<div class="phones index content">
    <?= $this->AuthLink->link(__('New Phone'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Phones') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('phone') ?></th>
                    <th><?= $this->Paginator->sort('use_for_billing') ?></th>
                    <th><?= $this->Paginator->sort('use_for_outages') ?></th>
                    <th><?= $this->Paginator->sort('use_for_commercial') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phones as $phone) : ?>
                <tr>
                    <td>
                        <?= $phone->has('customer') ? $this->Html->link(
                            $phone->customer->name,
                            ['controller' => 'Customers', 'action' => 'view', $phone->customer->id]
                        ) : '' ?>
                    </td>
                    <td><?= $phone->has('customer') ? h($phone->customer->number) : '' ?></td>
                    <td><?= h($phone->phone) ?></td>
                    <td><?= $phone->use_for_billing ? __('Yes') : __('No'); ?></td>
                    <td><?= $phone->use_for_outages ? __('Yes') : __('No'); ?></td>
                    <td><?= $phone->use_for_commercial ? __('Yes') : __('No'); ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $phone->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $phone->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $phone->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $phone->id)]
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
