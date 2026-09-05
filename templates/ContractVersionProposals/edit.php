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
            <?= $this->AuthLink->link(
                __('Take the Snapshot Again'),
                ['action' => 'refreshSnapshot', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php
            // What is written down about the proposal is offered here as well as on the detail:
            // somebody filling in the day it went out or the day it was signed reaches for Edit
            // first. No guard is needed - a proposal that may be edited is open and has not been
            // sent.
            ?>
            <?= $this->AuthLink->link(
                __('Record That the Papers Went Out'),
                ['action' => 'send', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                $contractVersionProposal->hasBeenConcluded()
                    ? __('Correct the Signature')
                    : __('Record the Signature'),
                ['action' => 'conclude', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Carry Over'),
                ['action' => 'transfer', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('List Proposals'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals form content">
            <?= $this->element('ContractVersionProposals/heading') ?>

            <?= $this->Form->create($contractVersionProposal) ?>
            <?= $this->element('ContractVersionProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
