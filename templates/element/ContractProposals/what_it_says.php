<?php
/**
 * What a proposal says about one contract: what would be billed for, what it asks of the records,
 * and what was confirmed before it was drawn up.
 *
 * Read from the proposal's own page and from the page of the proposal it is a part of. There is
 * one proposal from the office's side, so what it does for each contract is read where the rest of
 * it is - and changed there too, because a billing line that can only be edited somewhere else is
 * a second place to go looking.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var array<array{billing: \App\Model\Entity\Billing, line: \App\Contracts\Proposal\ProposedBilling|null, ending: bool, stopped: bool}> $rows
 * @var list<\App\Contracts\Proposal\PlannedChange> $planned
 * @var \App\Contracts\Proposal\ProposalConfirmations $confirmations
 * @var bool $mayBeEdited
 */

use App\Contracts\Proposal\ProposalConfirmations;

$answered = $confirmations->toArray();
?>
<?php
// Passed through rather than left to be found: what one element was handed does not reach the
// next, only what the page itself set.
?>
<?= $this->element('ContractProposals/proposed_billings', [
    'contractProposal' => $contractProposal,
    'rows' => $rows,
    'mayBeEdited' => $mayBeEdited,
]) ?>

<?php if ($planned !== []) : ?>
    <h4><?= __('What it asks of the records') ?></h4>
    <?= $this->element('ContractProposals/planned_changes', [
        'preview' => false,
        'planned' => $planned,
    ]) ?>
<?php endif; ?>

<?php if ($answered !== []) : ?>
    <h4><?= __('What was confirmed') ?></h4>
    <table>
        <?php foreach (ProposalConfirmations::QUESTIONS as $question) : ?>
            <?php if (array_key_exists($question, $answered)) : ?>
        <tr>
            <th><?= h(ProposalConfirmations::label($question)) ?></th>
            <td><?= $answered[$question] ? __('Yes') : __('No') ?></td>
        </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
