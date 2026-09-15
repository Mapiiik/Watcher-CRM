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
            <?php
            // Sending, signing and carrying over happen on the proposal and reach everything in
            // it, so they are not offered here - the way up to them is.
            ?>
            <?= $this->AuthLink->link(
                __('The Proposal These Are Part Of'),
                [
                    'plugin' => null,
                    'controller' => 'CustomerProposals',
                    'action' => 'view',
                    $contractProposal->customer_proposal_id,
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->Form->create($contractProposal) ?>
            <?= $this->element('ContractProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
