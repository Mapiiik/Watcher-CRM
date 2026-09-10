<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 */

use Cake\I18n\Date;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals form content">
            <?= $this->element('CustomerProposals/heading') ?>

            <?= $this->Form->create($customerProposal) ?>
            <fieldset>
                <legend><?= __('Record the Signature') ?></legend>
                <p><?= __('This is where the round ends. Nothing stands behind it waiting to be'
                    . ' written, so the day the customer agreed is the last thing it needs.') ?></p>
                <?= $this->Form->control('conclusion_date', [
                    'default' => Date::now(),
                    'label' => __('Conclusion Date'),
                    'help' => __('The day the customer agreed to it.'),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
