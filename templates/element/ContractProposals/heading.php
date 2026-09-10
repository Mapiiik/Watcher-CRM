<?php
/**
 * Names the proposal: the contract it belongs to, said the way every page about that contract
 * says it, and then the day these papers take effect and where they stand.
 *
 * Every page that acts on a proposal opens with this, so that the heading says which record is
 * being looked at rather than which button was pressed to get there. What the page then does is
 * the legend of its form, and the rule at the end keeps the two apart - without it the first
 * legend reads as part of the heading.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 */
?>
<?= $this->element('Contracts/heading', ['contract' => $contractProposal->contract]) ?>
<br>
<?= __('Purpose') ?><h3><?= h(__(
    '{0} from {1}',
    $contractProposal->purpose->label(),
    $contractProposal->effective_from,
)) ?></h3>
<h5><?= h($contractProposal->getState()) ?></h5>
<hr />
