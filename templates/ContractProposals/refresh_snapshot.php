<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->element('ContractProposals/heading') ?>

            <?= $this->Form->create($contractProposal) ?>
            <fieldset>
                <legend><?= __('Take the Snapshot Again') ?></legend>
                <p><?= __(
                    'The snapshot is what the papers print from. Taking it again reads the contract'
                    . ' as it stands now, so the papers say what is there today rather than what was'
                    . ' there when the proposal was drawn up.',
                ) ?></p>
                <p><?= __(
                    'What the proposal asks for is kept. Only a line about a billing that has since'
                    . ' left the contract is taken back, because there is nothing left for it to'
                    . ' act on.',
                ) ?></p>
            </fieldset>
            <?= $this->element('ContractProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
