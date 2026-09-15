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
    <h5><?= __('Worth knowing first') ?></h5>
    <ul>
        <?php foreach ($found as $one) : ?>
            <li><?= h($one['said']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($changesNothing) : ?>
    <p><?= __('These papers change nothing. They are the record of what went out, and carrying'
        . ' them over only marks them as dealt with.') ?></p>
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
    <h5><?= __('What else will be written') ?></h5>
    <p><?=
        __(
            'Not all of it was asked for. Carrying the papers over records the signature on the'
            . ' version, counts an amendment and closes the version being replaced, whether or not'
            . ' the papers say so.',
        )
        ?></p>
    <?= $this->element('ContractProposals/planned_changes', [
        'preview' => true,
        'planned' => $planned,
        ]) ?>
<?php endif; ?>
