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
            <br>
            <?php
            // What leads elsewhere sits apart from what is done here. Sending, signing and
            // applying the changes happen on the proposal and reach everything in it, so they are not
            // offered here - the way up to them is.
            ?>
            <?= $this->AuthLink->link(
                __('View Customer Proposal'),
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
