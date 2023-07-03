<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DealerCommission $dealerCommission
 * @var \Cake\Collection\CollectionInterface|array<string> $commissions
 * @var \Cake\Collection\CollectionInterface|array<string> $dealers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('List Dealer Commissions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="dealerCommissions form content">
            <?= $this->Form->create($dealerCommission) ?>
            <fieldset>
                <legend><?= __('Add Dealer Commission') ?></legend>
                <?php
                    echo $this->Form->control('dealer_id', ['options' => $dealers, 'empty' => true]);
                    echo $this->Form->control('commission_id', ['options' => $commissions, 'empty' => true]);
                    echo $this->Form->control('fixed');
                    echo $this->Form->control('percentage');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
