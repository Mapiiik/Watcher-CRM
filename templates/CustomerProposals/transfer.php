<?php
/**
 * Carrying the whole proposal over: what each of its contracts would get, and the one button.
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

// A package that asks for nothing still has to be carried over, or it reads as waiting for ever -
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
                'doing' => __('Carry the Proposal Over'),
            ]) ?>
            <p><?= __('Everything this proposal asks of the contracts, written into the live'
                . ' records in one go. Until now they have not moved.') ?></p>
        </div>
        <br>
            <?php foreach ($parts as $part) : ?>
                <div class="customerProposals view content">
                <?= $this->element('ContractProposals/carrying_over', [
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
                <p><?= __('This proposal cannot be carried over as it stands. What stands in the'
                    . ' way is said above, beside the contract it is about.') ?></p>
            </fieldset>
            <?php else : ?>
                <?= $this->Form->create(null, ['method' => 'post']) ?>
            <fieldset>
                <?= $this->legend(__('Carry the Proposal Over')) ?>
                <?php if ($changesNothing) : ?>
                    <p><?= __('This proposal changes nothing. It is the record of the papers that'
                        . ' went out, and carrying it over only marks it as dealt with, so that it'
                        . ' stops being listed as waiting.') ?></p>
                <?php else : ?>
                    <p><?= __('All of it or none of it. Half a package written into the records is'
                        . ' worse than none, because nothing afterwards says which half.') ?></p>
                <?php endif; ?>
                <?php if ($closed_period_override) : ?>
                    <?= $this->Form->control(BillingsTable::ALLOW_CLOSED_PERIODS, [
                        'type' => 'checkbox',
                        'label' => __('Write into a period that has already been invoiced for'),
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
                    $changesNothing ? __('Mark as Dealt With') : __('Carry Over'),
                    [
                        'confirm' => $changesNothing
                            ? __('Mark this proposal as dealt with?')
                            : __('Do you really want to write what this proposal asks for into the'
                                . ' live records?'),
                    ],
                ) ?>
                <?= $this->Form->end() ?>
            <?php endif; ?>
        </div>
    </div>
</div>
