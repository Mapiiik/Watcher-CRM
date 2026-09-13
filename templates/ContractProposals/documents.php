<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <br>
            <?= $this->AuthLink->link(
                __('Print'),
                [
                    'controller' => 'Contracts',
                    'action' => 'print',
                    $contractProposal->contract_id,
                    '?' => ['proposal_id' => $contractProposal->id],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Contract Documents'),
                ['controller' => 'Contracts', 'action' => 'documents', $contractProposal->contract_id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals view content">
            <?= $this->element('ContractProposals/heading', ['doing' => __('Documents')]) ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('Add Received Document'),
                    ['action' => 'addPages', $contractProposal->id],
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
                    ['contractProposal', $contractProposal->id],
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
                    ['contractProposal', $contractProposal->id],
                    ['generatedByUs' => true, 'manage' => true, 'thumbnails' => true],
                ) ?>
            </div>
        </div>
    </div>
</div>
