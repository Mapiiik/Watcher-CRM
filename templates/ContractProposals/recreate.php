<?php
/**
 * Putting a proposal together again from the contract as it stands now.
 *
 * What the papers are about is not asked here. All of it was photographed or written into the
 * changes when the papers were drawn up, so asking it again would leave them printing one thing
 * and doing another - which is why wanting any of it different means new papers. What is left is
 * what nothing is worked out from, and the fresh photograph that is the reason to come at all.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var array<string> $questions
 * @var array<string, string> $wording
 * @var array<string, string> $rounds
 * @var array<string, string> $contractNumbers
 * @var array<\Files\Model\Entity\FileLink> $documentsToDiscard
 */

use App\Model\Enum\ProposalPurpose;

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
                __('View Customer Proposal'),
                [
                    'plugin' => null,
                    'controller' => 'CustomerProposals',
                    'action' => 'view',
                    $contractProposal->customer_proposal_id,
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals form content">
            <?= $this->element('ContractProposals/heading', [
                'doing' => __('Recreate Contract Proposal'),
            ]) ?>
            <?= $this->Form->create($contractProposal) ?>
            <fieldset>
                <p><?= __('A new snapshot of the contract is taken. The purpose, the contract'
                    . ' version and the dates cannot be changed here - delete the contract'
                    . ' proposal and create a new one instead.') ?></p>
                <br>
                <?php
                if ($rounds !== []) {
                    echo $this->Form->control('customer_proposal_id', [
                        'options' => $rounds,
                        'empty' => __('A new customer proposal'),
                        'label' => __('Part of the Customer Proposal'),
                        'help' => __('Contract proposals that are part of one customer proposal'
                            . ' are sent and signed together.'),
                    ]);
                }

                // Asked only where something is being ended, which is where the paper carries the
                // number - an ending, or a new contract replacing an earlier version.
                $endsSomething = $contractProposal->purpose === ProposalPurpose::Termination
                    || $contractProposal->terminatesAnotherVersion();

                if ($contractNumbers !== [] && $endsSomething && $contractProposal->keepsVersions()) {
                    echo $this->Form->control('terminated_contract_number', [
                        'options' => $contractNumbers,
                        'empty' => true,
                        'label' => __('Number of the contract being terminated'),
                    ]);
                }

                echo $this->Form->control('note');
                ?>
            </fieldset>

            <?php if ($questions !== []) : ?>
            <fieldset>
                <legend><?= __('Before the contract proposal is created') ?></legend>
                <?php
                // A fresh reading of the contract may raise a question that was answered against
                // the old one, so they are asked here rather than on a page of their own.
                foreach ($questions as $question) :
                    ?>
                    <?= $this->Form->control("confirmations.{$question}", [
                        'type' => 'checkbox',
                        'checked' => $contractProposal->confirmations()->confirms($question),
                        'label' => $wording[$question] ?? $question,
                    ]) ?>
                <?php endforeach; ?>
            </fieldset>
            <?php endif; ?>

            <?php if ($documentsToDiscard !== []) : ?>
            <fieldset>
                <legend><?= __('Generated Documents') ?></legend>
                <p><?= __('These were generated from the old snapshot and are deleted with it.'
                    . ' Received documents are kept.') ?></p>
                <ul>
                    <?php foreach ($documentsToDiscard as $document) : ?>
                        <li><?= h($document->name ?? $document->document_type) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?= $this->Form->control('discard_the_documents', [
                    'type' => 'checkbox',
                    'checked' => false,
                    'label' => __n(
                        'Delete this document.',
                        'Delete these {0} documents.',
                        count($documentsToDiscard),
                        count($documentsToDiscard),
                    ),
                    // Said at the moment of agreeing rather than at the moment of submitting: by
                    // then the operator is answering for the whole form and this is the one part
                    // of it that cannot be undone.
                    'onclick' => 'if (this.checked && !window.confirm('
                        . json_encode(__n(
                            'This document will be permanently deleted. Continue?',
                            'These {0} documents will be permanently deleted. Continue?',
                            count($documentsToDiscard),
                            count($documentsToDiscard),
                        ), JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT)
                        . ')) { this.checked = false; }',
                ]) ?>
            </fieldset>
            <?php endif; ?>

            <?= $this->Form->button(__('Recreate Contract Proposal')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
