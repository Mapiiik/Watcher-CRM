<?php
/**
 * Names the round of papers: whose they are, what they are for, and where they stand.
 *
 * One heading rather than two. Opening with the customer the way a page about the customer opens
 * made every proposal page look like the customer's own, and the number says whose they are
 * without a block of its own.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var string|null $doing What the page holding this is about the papers, if not the papers.
 */
?>
<?= $this->record(
    __('Customer No.'),
    $customerProposal->customer->number . ' - ' . __(
        '{0} from {1}',
        $customerProposal->purpose->label(),
        $customerProposal->effective_from,
    ),
    (string)$customerProposal->getState(),
    $doing ?? __('Customer Proposal'),
) ?>
<hr />
