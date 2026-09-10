<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('View Proposal'),
                ['action' => 'view', $contractVersionProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                [
                    'controller' => 'Contracts',
                    'action' => 'print',
                    $contractVersionProposal->contract_id,
                    '?' => ['proposal_id' => $contractVersionProposal->id],
                ],
                ['class' => 'side-nav-item', 'target' => 'print'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Contract Documents'),
                ['controller' => 'Contracts', 'action' => 'documents', $contractVersionProposal->contract_id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('View Contract'),
                ['controller' => 'Contracts', 'action' => 'view', $contractVersionProposal->contract_id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractVersionProposals view content">
            <?= $this->element('ContractVersionProposals/heading') ?>
            <div class="related">
                <?= $this->AuthLink->link(
                    __('Add Received Document'),
                    ['action' => 'addPages', $contractVersionProposal->id],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __('Received Documents') ?></h4>
                <p><?=
                    __(
                        'Scans arrive a page at a time and often out of order. Add what has come,'
                        . ' and put the pages the way the paper reads.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['proposal', $contractVersionProposal->id],
                    ['ours' => false, 'manage' => true],
                ) ?>
            </div>
            <br>
            <div class="related">
                <h4><?= __('Sent Documents') ?></h4>
                <p><?=
                    __(
                        'A paper is drawn once and handed back afterwards, so what is here is what'
                        . ' the customer was given. Letting go of one lets the document be drawn'
                        . ' again.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['proposal', $contractVersionProposal->id],
                    ['ours' => true, 'manage' => true],
                ) ?>
            </div>
        </div>
    </div>
</div>
