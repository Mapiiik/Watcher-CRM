<?php
/**
 * Names the proposal: which contract it belongs to, what the papers are for, and where they stand.
 *
 * One heading rather than two. Opening with the contract the way a page about the contract opens
 * made every proposal page look like the contract's own, and the number says which contract it is
 * without a block of its own.
 *
 * Every page that acts on a proposal opens with this, so that the heading says which record is
 * being looked at rather than which button was pressed to get there - and says which of those
 * pages it is, in front of it. The rule at the end keeps the heading off the form below.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var string|null $doing What the page holding this is about the proposal, if not the proposal.
 */
?>
<?= $this->record(
    __('Contract No.'),
    $contractProposal->contract->number . ' - ' . __(
        '{0} from {1}',
        $contractProposal->purpose->label(),
        $contractProposal->effective_from,
    ),
    (string)$contractProposal->getState(),
    $doing ?? __('Contract Proposal'),
) ?>
<hr />
