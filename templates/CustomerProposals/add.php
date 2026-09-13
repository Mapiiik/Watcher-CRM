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
            <?= $this->AuthLink->link(
                __('List Proposals'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals form content">
            <?= $this->heading(__('Add Customer Proposal')) ?>
            <hr />
            <?= $this->Form->create($customerProposal) ?>
            <?= $this->element('CustomerProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
