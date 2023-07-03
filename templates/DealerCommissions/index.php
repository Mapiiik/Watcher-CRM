<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\DealerCommission> $dealerCommissions
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

<div class="dealerCommissions index content">
    <?= $this->AuthLink->link(
        __('New Dealer Commission'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Dealer Commissions') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('dealer_id') ?></th>
                    <th><?= $this->Paginator->sort('commission_id') ?></th>
                    <th><?= $this->Paginator->sort('fixed') ?></th>
                    <th><?= $this->Paginator->sort('percentage') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dealerCommissions as $dealerCommission) : ?>
                <tr>
                    <td>
                        <?= $dealerCommission->has('dealer') ? $this->Html->link(
                            $dealerCommission->dealer->name,
                            ['controller' => 'Customers', 'action' => 'view', $dealerCommission->dealer->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $dealerCommission->has('commission') ? $this->Html->link(
                            $dealerCommission->commission->name,
                            ['controller' => 'Commissions', 'action' => 'view', $dealerCommission->commission->id]
                        ) : '' ?>
                    </td>
                    <td><?= $this->Number->format($dealerCommission->fixed) ?></td>
                    <td><?= $this->Number->format($dealerCommission->percentage) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $dealerCommission->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $dealerCommission->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $dealerCommission->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $dealerCommission->id)]
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
