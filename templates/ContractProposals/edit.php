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
            <?= $this->AuthLink->link(
                __('Take the Snapshot Again'),
                ['action' => 'refreshSnapshot', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php
            // What is written down about the proposal is offered here as well as on the detail:
            // somebody filling in the day it went out or the day it was signed reaches for Edit
            // first. No guard is needed - a proposal that may be edited is open and has not been
            // sent.
            ?>
            <?= $this->AuthLink->link(
                __('Record the Sending'),
                ['action' => 'send', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                $contractProposal->hasBeenConcluded()
                    ? __('Correct the Signature')
                    : __('Record the Signature'),
                ['action' => 'conclude', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Carry Over'),
                ['action' => 'transfer', $contractProposal->id],
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
        <div class="contractProposals form content">
            <?= $this->element('ContractProposals/heading') ?>

            <?= $this->Form->create($contractProposal) ?>
            <?= $this->element('ContractProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
