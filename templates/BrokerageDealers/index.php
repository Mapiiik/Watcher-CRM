<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BrokerageDealer[]|\Cake\Collection\CollectionInterface $brokerageDealers
 */
?>
<div class="brokerageDealers index content">
    <?= $this->AuthLink->link(
        __('New Brokerage Dealer'),
        ['action' => 'add'],
        ['class' => 'button float-right win-link']
    ) ?>
    <h3><?= __('Brokerage Dealers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('dealer_id') ?></th>
                    <th><?= $this->Paginator->sort('brokerage_id') ?></th>
                    <th><?= $this->Paginator->sort('fixed') ?></th>
                    <th><?= $this->Paginator->sort('percentage') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brokerageDealers as $brokerageDealer) : ?>
                <tr>
                    <td><?= $this->Number->format($brokerageDealer->id) ?></td>
                    <td>
                        <?= $brokerageDealer->has('dealer') ? $this->Html->link(
                            $brokerageDealer->dealer->name,
                            ['controller' => 'Customers', 'action' => 'view', $brokerageDealer->dealer->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $brokerageDealer->has('brokerage') ? $this->Html->link(
                            $brokerageDealer->brokerage->name,
                            ['controller' => 'Brokerages', 'action' => 'view', $brokerageDealer->brokerage->id]
                        ) : '' ?>
                    </td>
                    <td><?= $this->Number->format($brokerageDealer->fixed) ?></td>
                    <td><?= $this->Number->format($brokerageDealer->percentage) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $brokerageDealer->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $brokerageDealer->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $brokerageDealer->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $brokerageDealer->id)]
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
