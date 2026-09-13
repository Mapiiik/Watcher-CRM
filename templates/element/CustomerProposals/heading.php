<?php
/**
 * Names the round of papers: what they are for, and where they stand.
 *
 * Whose they are is not said here. The bar across the top of the page already carries the
 * customer, and every page about a round is reached under them, so opening with the number said
 * twice what the page had said once and pushed the papers themselves into second place.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var string|null $doing What the page holding this is about the papers, if not the papers.
 */
?>
<?= $this->record(
    __('Customer Proposal'),
    __(
        '{0} from {1}',
        $customerProposal->purpose->label(),
        $customerProposal->effective_from,
    ),
    (string)$customerProposal->getState(),
    $doing ?? null,
) ?>
<br>
