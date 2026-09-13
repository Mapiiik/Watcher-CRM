<?php
/**
 * Names the proposal: what the papers are for, and where they stand.
 *
 * Which contract it belongs to is not said here. The bar across the top of the page already
 * carries it, and every page about a proposal is reached under the contract, so opening with the
 * number said twice what the page had said once and pushed the papers themselves into second
 * place.
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
    __('Contract Proposal'),
    __(
        '{0} from {1}',
        $contractProposal->purpose->label(),
        $contractProposal->effective_from,
    ),
    (string)$contractProposal->getState(),
    $doing ?? null,
) ?>
<hr />
