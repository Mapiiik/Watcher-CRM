<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var array<int|string, string> $deliveryMethods
 */

use Cake\I18n\Date;

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
            <fieldset>
                <legend><?= __('Record That the Papers Went Out') ?></legend>
                <p><?= __(
                    'Once this is recorded, what the papers stand on is settled: the snapshot, what'
                    . ' the proposal asks for and what was confirmed can no longer be changed.'
                    . ' A correction is a new proposal.',
                ) ?></p>
                <?php
                echo $this->Form->control('sent_date', [
                    'default' => Date::now(),
                    'label' => __('Sent On'),
                ]);
                echo $this->Form->control('sent_by', [
                    'options' => $deliveryMethods,
                    'empty' => true,
                    'label' => __('Sent By'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
