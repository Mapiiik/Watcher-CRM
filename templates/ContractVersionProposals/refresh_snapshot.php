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
            <legend><?= __('Take the Snapshot Again') ?></legend>
            <p><?= __(
                'The billings below are as they stand now, not as the proposal took them.'
                . ' Saying yes replaces both the snapshot and what the proposal asks for.',
            ) ?></p>
            <?= $this->element('ContractVersionProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
