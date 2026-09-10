<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var \App\Contracts\Proposal\ProposalConfirmations $confirmations
 * @var bool $mayBeEdited
 * @var bool $mayBeDeleted
 * @var array<int|string, string> $deliveryMethods
 * @var array<array{billing: \App\Model\Entity\Billing, line: \App\Contracts\Proposal\ProposedBilling|null, ending: bool, stopped: bool}> $rows
 * @var array<string, array<string, array<\Files\Model\Entity\FileLink>>> $filed
 * @var array<string, string> $documentTypes
 * @var list<\App\Contracts\Proposal\PlannedChange> $planned
 */

use App\Contracts\Proposal\ProposalConfirmations;
use App\Model\Enum\DocumentVariant;

$ourPages = 0;
$theirPages = 0;
foreach ($filed as $byVariant) {
    foreach ($byVariant as $variant => $links) {
        if (DocumentVariant::tryFrom((string)$variant)?->isDrawnUpByUs() ?? false) {
            $ourPages += count($links);
        } else {
            $theirPages += count($links);
        }
    }
}

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if ($mayBeEdited) : ?>
                <?= $this->AuthLink->link(
                    __('Edit Proposal'),
                    ['action' => 'edit', $contractProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Take the Snapshot Again'),
                    ['action' => 'refreshSnapshot', $contractProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if ($contractProposal->isOpen()) : ?>
                <?= $this->AuthLink->link(
                    $contractProposal->hasBeenSent()
                        ? __('Record the Sending Again')
                        : __('Record the Sending'),
                    ['action' => 'send', $contractProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    $contractProposal->hasBeenConcluded()
                        ? __('Correct the Signature')
                        : __('Record the Signature'),
                    ['action' => 'conclude', $contractProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Carry Over'),
                    ['action' => 'transfer', $contractProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->link(
                __('Print to PDF'),
                [
                    'controller' => 'Contracts',
                    'action' => 'print',
                    $contractProposal->contract_id,
                    '?' => ['proposal_id' => $contractProposal->id],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Proposal Documents'),
                ['action' => 'documents', $contractProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php if ($contractProposal->isOpen()) : ?>
                <?= $this->AuthLink->postLink(
                    __('Revoke'),
                    ['action' => 'revoke', $contractProposal->id],
                    [
                        'class' => 'side-nav-item',
                        'confirm' => __('Give up on this proposal? The live records never moved.'),
                    ],
                ) ?>
            <?php endif; ?>
            <?php if ($mayBeDeleted) : ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['action' => 'delete', $contractProposal->id],
                    ['class' => 'side-nav-item', 'confirm' => __('Are you sure?')],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->link(
                __('List Proposals'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals view content">
            <?= $this->element('ContractProposals/heading') ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $this->Html->link(
                                h($contractProposal->contract->number ?? ''),
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $contractProposal->contract_id,
                                ],
                            ) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract Version') ?></th>
                            <td><?= $contractProposal->contract_version !== null
                                ? $this->Html->link(
                                    $contractProposal->contract_version->name,
                                    [
                                        'controller' => 'ContractVersions',
                                        'action' => 'view',
                                        $contractProposal->contract_version_id,
                                    ],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Effective From') ?></th>
                            <td><?= h($contractProposal->effective_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Purpose') ?></th>
                            <td><?= h($contractProposal->purpose->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Snapshot Taken') ?></th>
                            <td><?= h($contractProposal->snapshot_taken) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Sent To The Customer') ?></th>
                            <td><?= h($contractProposal->getSending()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Conclusion Date') ?></th>
                            <td><?= h($contractProposal->conclusion_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Papers on File') ?></th>
                            <td><?=
                                $this->Html->link(
                                    $ourPages + $theirPages === 0
                                        ? __('None')
                                        : __(
                                            '{0} drawn up, {1} came back',
                                            $ourPages,
                                            $theirPages,
                                        ),
                                    ['action' => 'documents', $contractProposal->id],
                                )
                                ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Carried Over') ?></th>
                            <td><?= h($contractProposal->applied) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Revoked') ?></th>
                            <td><?= h($contractProposal->revoked) ?></td>
                        </tr>
                        <?php if ($contractProposal->terminatesAnotherVersion()) : ?>
                        <tr>
                            <th><?= __('Terminates Contract Version') ?></th>
                            <td><?= $contractProposal->terminated_contract_version !== null
                                ? $this->Html->link(
                                    $contractProposal->terminated_contract_version->name,
                                    [
                                        'controller' => 'ContractVersions',
                                        'action' => 'view',
                                        $contractProposal->terminates_contract_version_id,
                                    ],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number of the contract being terminated') ?></th>
                            <td><?= h($contractProposal->terminated_contract_number) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $contractProposal]) ?>
                </div>
            </div>

            <?= $this->element('ContractProposals/proposed_billings') ?>

            <?php if ($planned !== []) : ?>
                <h4><?= __('What it asks of the records') ?></h4>
                <?= $this->element('ContractProposals/planned_changes', [
                    'preview' => false,
                ]) ?>
            <?php endif; ?>

            <?php $answered = $confirmations->toArray(); ?>
            <?php if ($answered !== []) : ?>
                <h4><?= __('What was confirmed') ?></h4>
                <table>
                <?php foreach (ProposalConfirmations::QUESTIONS as $question) : ?>
                    <?php if (array_key_exists($question, $answered)) : ?>
                    <tr>
                        <th><?= h(ProposalConfirmations::label($question)) ?></th>
                        <td><?= $answered[$question] ? __('Yes') : __('No') ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php if ($filed !== []) : ?>
                <h4><?= __('Papers on File') ?></h4>
                <h5><?= __('Received Documents') ?></h5>
                <?= $this->cell(
                    'Documents',
                    ['proposal', $contractProposal->id],
                    ['ours' => false],
                ) ?>
                <h5><?= __('Sent Documents') ?></h5>
                <?= $this->cell(
                    'Documents',
                    ['proposal', $contractProposal->id],
                    ['ours' => true],
                ) ?>
            <?php endif; ?>

            <?php if (!empty($contractProposal->note)) : ?>
                <h4><?= __('Note') ?></h4>
                <blockquote><?= $this->Text->autoParagraph(h($contractProposal->note)) ?></blockquote>
            <?php endif; ?>
        </div>
    </div>
</div>
