<?php
/**
 * Names the proposal the way a contract and a version name themselves: a label, what it is, and
 * a line underneath saying where it stands.
 *
 * Every page that acts on a proposal opens with this, so that the heading says which record is
 * being looked at rather than which button was pressed to get there. What the page then does is
 * the legend of its form, and the rule at the end keeps the two apart - without it the first
 * legend reads as part of the heading.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 */
?>
<?= __('Contract No.') ?><h3><?= h($contractVersionProposal->contract->number ?? '') ?></h3>
<?= __('Effective From') ?><h3><?= h($contractVersionProposal->effective_from) ?></h3>
<h5><?= h($contractVersionProposal->getState()) ?></h5>
<hr />
