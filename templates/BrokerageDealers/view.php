<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\BrokerageDealer $brokerageDealer
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Brokerage Dealer'),
                ['action' => 'edit', $brokerageDealer->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Brokerage Dealer'),
                ['action' => 'delete', $brokerageDealer->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $brokerageDealer->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Brokerage Dealers'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(
                __('New Brokerage Dealer'),
                ['action' => 'add'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="brokerageDealers view content">
            <h3><?= h($brokerageDealer->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Dealer') ?></th>
                    <td><?= $brokerageDealer->has('dealer') ? $this->Html->link(
                        $brokerageDealer->dealer->name,
                        ['controller' => 'Customers', 'action' => 'view', $brokerageDealer->dealer->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Brokerage') ?></th>
                    <td><?= $brokerageDealer->has('brokerage') ? $this->Html->link(
                        $brokerageDealer->brokerage->name,
                        ['controller' => 'Brokerages', 'action' => 'view', $brokerageDealer->brokerage->id]
                    ) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Fixed') ?></th>
                    <td><?= $this->Number->format($brokerageDealer->fixed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Percentage') ?></th>
                    <td><?= $this->Number->format($brokerageDealer->percentage) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($brokerageDealer->id) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
