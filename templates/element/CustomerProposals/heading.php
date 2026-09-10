<?php
/**
 * Names the round: the customer it is for, said the way every page about that customer says it,
 * and then what the papers are for and where they got to.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 */
?>
<?= $this->element('Customers/heading', ['customer' => $customerProposal->customer]) ?>
<br>
<?= __('Purpose') ?><h3><?= h(__(
    '{0} from {1}',
    $customerProposal->purpose->label(),
    $customerProposal->effective_from,
)) ?></h3>
<h5><?= h($customerProposal->getState()) ?></h5>
<hr />
