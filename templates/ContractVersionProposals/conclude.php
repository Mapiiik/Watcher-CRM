<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 */

use Cake\I18n\Date;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals form content">
            <?= $this->element('ContractVersionProposals/heading') ?>

            <?= $this->Form->create($contractVersionProposal) ?>
            <fieldset>
                <legend><?= __('Record the Signature') ?></legend>
                <p><?= __(
                    'Nothing is carried over into the live records without this day. What the'
                    . ' proposal asks for stays as it is, and the records move only when it is'
                    . ' carried over.',
                ) ?></p>
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
