<?php
/**
 * What carrying one contract's papers over would do.
 *
 * Spelled out a contract at a time: what each part asks about is that contract, and one merged
 * list would say what is happening to nothing in particular.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var array<int, array{what: string, said: string}> $found
 * @var array<\App\Model\Entity\Billing> $billingsNow
 * @var array<\App\Model\Entity\Billing> $billingsAfterwards
 * @var list<\App\Contracts\Proposal\PlannedChange> $planned
 */

$changesNothing = $contractProposal->proposedChanges()->isEmpty();
?>
<h4><?= h($contractProposal->getName()) ?></h4>

<?php if ($found !== []) : ?>
    <h5><?= __('Before you continue') ?></h5>
    <ul>
        <?php foreach ($found as $one) : ?>
            <li><?= h($one['said']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($changesNothing) : ?>
    <p><?= __('This contract proposal changes nothing. It records what was sent, and applying'
        . ' it only marks it as settled.') ?></p>
<?php else : ?>
<div class="row">
    <div class="column">
        <h5><?= __('As it is now') ?></h5>
        <?= $this->element('ContractProposals/billings', ['billings' => $billingsNow]) ?>
    </div>
    <div class="column">
        <h5><?= __('As it would be') ?></h5>
        <?= $this->element('ContractProposals/billings', ['billings' => $billingsAfterwards]) ?>
    </div>
</div>
<?php endif; ?>

<?php if ($planned !== []) : ?>
    <h5><?= __('What else will be recorded') ?></h5>
    <p><?=
        __(
            'Applying the changes also records the signature on the version, counts the'
            . ' amendment and ends the version being replaced, even if the proposal does not say'
            . ' so.',
        )
        ?></p>
    <?= $this->element('ContractProposals/planned_changes', [
        'preview' => true,
        'planned' => $planned,
        ]) ?>
<?php endif; ?>
