<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BrokerageDealer[]|\Cake\Collection\CollectionInterface $brokerageDealers
 */
?>
<div class="brokerageDealers index content">
    <?= $this->Html->link(__('New Brokerage Dealer'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Brokerage Dealers') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('dealer_id') ?></th>
                    <th><?= $this->Paginator->sort('brokerage_id') ?></th>
                    <th><?= $this->Paginator->sort('fixed') ?></th>
                    <th><?= $this->Paginator->sort('percentage') ?></th>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brokerageDealers as $brokerageDealer): ?>
                <tr>
                    <td><?= $this->Number->format($brokerageDealer->dealer_id) ?></td>
                    <td><?= $brokerageDealer->has('brokerage') ? $this->Html->link($brokerageDealer->brokerage->name, ['controller' => 'Brokerages', 'action' => 'view', $brokerageDealer->brokerage->id]) : '' ?></td>
                    <td><?= $this->Number->format($brokerageDealer->fixed) ?></td>
                    <td><?= $this->Number->format($brokerageDealer->percentage) ?></td>
                    <td><?= $this->Number->format($brokerageDealer->id) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $brokerageDealer->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $brokerageDealer->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $brokerageDealer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $brokerageDealer->id)]) ?>
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
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
