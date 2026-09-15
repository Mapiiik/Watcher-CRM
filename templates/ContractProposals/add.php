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
            <?php
            // Where these papers live. The address the form was opened under comes along, so it
            // lands on the customer or the contract being worked on.
            ?>
            <?= $this->AuthLink->link(
                __('Documents'),
                ['plugin' => null, 'controller' => 'Documents', 'action' => 'manage'],
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
