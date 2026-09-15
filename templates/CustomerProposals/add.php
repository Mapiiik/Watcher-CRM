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
        <div class="customerProposals form content">
            <?= $this->Form->create($customerProposal) ?>
            <?= $this->element('CustomerProposals/form') ?>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
