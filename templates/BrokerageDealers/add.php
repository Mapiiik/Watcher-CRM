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
            <?= $this->Html->link(__('List Brokerage Dealers'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="brokerageDealers form content">
            <?= $this->Form->create($brokerageDealer) ?>
            <fieldset>
                <legend><?= __('Add Brokerage Dealer') ?></legend>
                <?php
                    echo $this->Form->control('dealer_id');
                    echo $this->Form->control('brokerage_id', ['options' => $brokerages, 'empty' => true]);
                    echo $this->Form->control('fixed');
                    echo $this->Form->control('percentage');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
