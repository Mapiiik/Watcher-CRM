<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DealerCommission $dealerCommission
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $dealerCommission->id],
                [
                    'confirm' => __('Are you sure you want to delete # {0}?', $dealerCommission->id),
                    'class' => 'side-nav-item',
                ]
            ) ?>
            <?= $this->AuthLink->link(
                __('List Dealer Commissions'),
                ['action' => 'index'],
                ['class' => 'side-nav-item']
            ) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="dealerCommissions form content">
            <?= $this->Form->create($dealerCommission) ?>
            <fieldset>
                <legend><?= __('Edit Dealer Commission') ?></legend>
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
