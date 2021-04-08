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
            <?= $this->Html->link(__('Edit Brokerage Dealer'), ['action' => 'edit', $brokerageDealer->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Brokerage Dealer'), ['action' => 'delete', $brokerageDealer->id], ['confirm' => __('Are you sure you want to delete # {0}?', $brokerageDealer->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Brokerage Dealers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Brokerage Dealer'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="brokerageDealers view content">
            <h3><?= h($brokerageDealer->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Brokerage') ?></th>
                    <td><?= $brokerageDealer->has('brokerage') ? $this->Html->link($brokerageDealer->brokerage->name, ['controller' => 'Brokerages', 'action' => 'view', $brokerageDealer->brokerage->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Dealer Id') ?></th>
                    <td><?= $this->Number->format($brokerageDealer->dealer_id) ?></td>
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
