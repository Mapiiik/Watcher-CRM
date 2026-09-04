<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 */
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
            <?= $this->Form->create($contractVersionProposal) ?>
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
            <?= $this->element('ContractVersionProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
