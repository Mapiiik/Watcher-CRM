<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<string, string> $purposes
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string, string> $customers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if (!$customerProposal->isNew()) : ?>
                <?= $this->AuthLink->link(
                    __('View Proposal'),
                    ['action' => 'view', $customerProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php
            // What is written down about the round is offered here as well as on the detail:
            // somebody filling in the day it went out or the day it was signed reaches for Edit
            // first. No guard is needed - a round that may be edited has not been given up on.
            ?>
            <?= $this->AuthLink->link(
                __('Record the Sending'),
                ['action' => 'send', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Record the Signature'),
                ['action' => 'conclude', $customerProposal->id],
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
        <div class="customerProposals form content">
            <?= $this->element('CustomerProposals/heading') ?>

            <?= $this->Form->create($customerProposal) ?>
            <?= $this->element('CustomerProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
