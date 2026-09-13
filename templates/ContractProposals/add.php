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
                __('List Proposals'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->heading(__('Add Contract Proposal')) ?>
            <hr />
            <?= $this->Form->create($contractProposal) ?>
            <?= $this->element('ContractProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
