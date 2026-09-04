<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var array<int, array{what: string, said: string}> $found
 * @var bool $stopped
 * @var array<\App\Model\Entity\Billing> $billingsNow
 * @var array<\App\Model\Entity\Billing> $billingsAfterwards
 * @var bool $closed_period_override
 */

use App\Model\Table\BillingsTable;

$changesNothing = $contractVersionProposal->proposedChanges()->isEmpty();
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if (!$contractVersionProposal->hasBeenConcluded()) : ?>
                <?= $this->AuthLink->link(
                    __('Record the Signature'),
                    ['action' => 'conclude', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals view content">
            <?= $this->element('ContractVersionProposals/heading') ?>

            <?php if ($found !== []) : ?>
                <h4><?= __('Worth knowing first') ?></h4>
                <ul>
                    <?php foreach ($found as $one) : ?>
                        <li><?= h($one['said']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!$changesNothing) : ?>
            <div class="row">
                <div class="column">
                    <h4><?= __('As it is now') ?></h4>
                    <?= $this->element('ContractVersionProposals/billings', [
                        'billings' => $billingsNow,
                    ]) ?>
                </div>
                <div class="column">
                    <h4><?= __('As it would be') ?></h4>
                    <?= $this->element('ContractVersionProposals/billings', [
                        'billings' => $billingsAfterwards,
                    ]) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <hr />
        <div class="contractVersionProposals form content">
            <?php if ($stopped) : ?>
            <fieldset>
                <legend><?= __('Carry the Proposal Over') ?></legend>
                <p><?= __('This proposal cannot be carried over as it stands.') ?></p>
            </fieldset>
            <?php else : ?>
                <?= $this->Form->create(null, ['method' => 'post']) ?>
            <fieldset>
                <legend><?= __('Carry the Proposal Over') ?></legend>
                <?php if ($changesNothing) : ?>
                    <p><?= __('This proposal changes nothing; it is the record of the papers that'
                        . ' went out. Carrying it over only marks it as dealt with, so that it stops'
                        . ' being listed as waiting.') ?></p>
                <?php else : ?>
                    <p><?= __('Until now the live records have not moved. This is where they do.') ?></p>
                <?php endif; ?>
                <?php if ($closed_period_override) : ?>
                    <?= $this->Form->control(BillingsTable::ALLOW_CLOSED_PERIODS, [
                        'type' => 'checkbox',
                        'label' => __('Write into a period that has already been invoiced for'),
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
