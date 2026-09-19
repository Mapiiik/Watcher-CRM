<?php
/**
 * Applying the changes of the whole proposal: what each of its contracts would get, and the one button.
 *
 * All or none. A package half written into the records is the worst of both - nothing says which
 * half, and the papers it came from read as settled either way.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<array<string, mixed>> $parts
 * @var bool $stopped
 * @var bool $closed_period_override
 * @var bool $below_minimum_override
 */

use App\Model\Table\BillingsTable;

// A package that asks for nothing still has to be applied, or it reads as waiting for ever -
// but what happens then is that it stops being listed, and the button says so.
$changesNothing = true;

foreach ($parts as $part) {
    $changesNothing = $changesNothing && $part['papers']->proposedChanges()->isEmpty();
}
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => [
                        'proposal_id' => $customerProposal->id,
                        'agenda' => 'CustomerProposals',
                    ],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals view content">
            <?= $this->element('CustomerProposals/heading', [
                'doing' => __('Apply the Proposal\'s Changes'),
            ]) ?>
            <p><?= __('Everything this proposal changes on the contracts is recorded at once.'
                . ' Until then, nothing on them has changed.') ?></p>
        </div>
        <br>
            <?php foreach ($parts as $part) : ?>
                <div class="customerProposals view content">
                <?= $this->element('ContractProposals/applying_changes', [
                    'contractProposal' => $part['papers'],
                    'found' => $part['found'],
                    'planned' => $part['planned'],
                    'billingsNow' => $part['billingsNow'],
                    'billingsAfterwards' => $part['billingsAfterwards'],
                ]) ?>
                </div>
                <br>
            <?php endforeach; ?>
        <div class="customerProposals form content">
            <?php if ($stopped) : ?>
            <fieldset>
                <p><?= __('The changes of this proposal cannot be applied in its current state.'
                    . ' What prevents it is shown above, next to the contract concerned.') ?></p>
            </fieldset>
            <?php else : ?>
                <?= $this->Form->create(null, ['method' => 'post']) ?>
            <fieldset>
                <?= $this->legend(__('Apply the Proposal\'s Changes')) ?>
                <?php if ($changesNothing) : ?>
                    <p><?= __('This proposal changes nothing. It records what was sent, and'
                        . ' applying it only marks it as settled so that it is no longer listed'
                        . ' as pending.') ?></p>
                <?php else : ?>
                    <p><?= __('Either all of the changes are applied, or none of them. A partly'
                        . ' applied proposal would leave no trace of which part went through.') ?></p>
                <?php endif; ?>
                <?php if ($closed_period_override) : ?>
                    <?= $this->Form->control(BillingsTable::ALLOW_CLOSED_PERIODS, [
                        'type' => 'checkbox',
                        'label' => __('Allow changes in an already invoiced period'),
                    ]) ?>
                <?php endif; ?>
                <?php if ($below_minimum_override) : ?>
                    <?= $this->Form->control(BillingsTable::ALLOW_BELOW_MINIMUM, [
                        'type' => 'checkbox',
                        'label' => __('Allow a connection price below the minimum set on the contract'),
                    ]) ?>
                <?php endif; ?>
            </fieldset>
                <?= $this->Form->button(
                    $changesNothing ? __('Mark as Settled') : __('Apply Changes'),
                    [
                        'confirm' => $changesNothing
                            ? __('Mark this proposal as settled?')
                            : __('Do you really want to apply the changes of this proposal?'),
                    ],
                ) ?>
                <?= $this->Form->end() ?>
            <?php endif; ?>
        </div>
    </div>
</div>
