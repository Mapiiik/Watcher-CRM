<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                [
                    'controller' => 'Customers',
                    'action' => 'print',
                    $customerProposal->customer_id,
                    '?' => ['proposal_id' => $customerProposal->id],
                ],
                ['class' => 'side-nav-item', 'target' => 'print'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Customer Documents'),
                ['controller' => 'Customers', 'action' => 'documents', $customerProposal->customer_id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals view content">
            <?= $this->element('CustomerProposals/heading', ['doing' => __('Documents')]) ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('Add Received Document'),
                    ['action' => 'addPages', $customerProposal->id],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Received Documents') ?></h4>
                <p><?=
                    __(
                        'Scans arrive a page at a time and often out of order. Add what has come,'
                        . ' and put the pages the way the paper reads.',
                    )
                    ?></p>
                <?php $this->Preview->load() ?>
                <?= $this->cell(
                    'Documents',
                    ['customerProposal', $customerProposal->id],
                    ['generatedByUs' => false, 'manage' => true, 'thumbnails' => true],
                ) ?>
            </div>
            <div class="related">
                <h4><?= __('Generated Documents') ?></h4>
                <p><?=
                    __(
                        'A document is generated once and handed back afterwards, so what is here'
                        . ' is what the customer was given. Letting go of one lets it be generated'
                        . ' again.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['customerProposal', $customerProposal->id],
                    ['generatedByUs' => true, 'manage' => true, 'thumbnails' => true],
                ) ?>
            </div>
        </div>
    </div>
</div>
