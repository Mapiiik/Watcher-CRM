<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaxRate $taxRate
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $taxRate->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taxRate->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Tax Rates'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="taxRates form content">
            <?= $this->Form->create($taxRate) ?>
            <fieldset>
                <legend><?= __('Edit Tax Rate') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('vat_rate');
                    echo $this->Form->control('reverse_charge');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
