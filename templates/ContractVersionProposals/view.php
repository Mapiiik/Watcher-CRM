<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var \App\Contracts\Proposal\ProposalChanges $changes
 * @var \App\Contracts\Proposal\ProposalConfirmations $confirmations
 * @var bool $mayBeEdited
 * @var bool $mayBeDeleted
 * @var array<int|string, string> $deliveryMethods
 * @var array<array{billing: \App\Model\Entity\Billing, line: \App\Contracts\Proposal\ProposedBilling|null, ending: bool}> $rows
 */

use App\Contracts\Proposal\ProposalConfirmations;
use App\Contracts\Proposal\ProposedContract;
use App\Contracts\Proposal\ProposedVersion;

?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?php if ($mayBeEdited) : ?>
                <?= $this->AuthLink->link(
                    __('Edit Proposal'),
                    ['action' => 'edit', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Take the Snapshot Again'),
                    ['action' => 'refreshSnapshot', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if ($contractVersionProposal->isOpen()) : ?>
                <?= $this->AuthLink->link(
                    $contractVersionProposal->hasBeenSent()
                        ? __('Record the Sending Again')
                        : __('Record the Sending'),
                    ['action' => 'send', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    $contractVersionProposal->hasBeenConcluded()
                        ? __('Correct the Signature')
                        : __('Record the Signature'),
                    ['action' => 'conclude', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Carry Over'),
                    ['action' => 'transfer', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?= $this->AuthLink->link(
                __('Print'),
                [
                    'controller' => 'Contracts',
                    'action' => 'print',
                    $contractVersionProposal->contract_id,
                    '?' => ['proposal_id' => $contractVersionProposal->id],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?php if ($contractVersionProposal->isOpen()) : ?>
                <?= $this->AuthLink->postLink(
                    __('Revoke'),
                    ['action' => 'revoke', $contractVersionProposal->id],
                    [
                        'class' => 'side-nav-item',
                        'confirm' => __('Give up on this proposal? The live records never moved.'),
                    ],
                ) ?>
            <?php endif; ?>
            <?php if ($mayBeDeleted) : ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['action' => 'delete', $contractVersionProposal->id],
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
        <div class="contractVersionProposals view content">
            <?= $this->element('ContractVersionProposals/heading') ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Contract') ?></th>
                            <td><?= $this->Html->link(
                                h($contractVersionProposal->contract->number ?? ''),
                                [
                                    'controller' => 'Contracts',
                                    'action' => 'view',
                                    $contractVersionProposal->contract_id,
                                ],
                            ) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract Version') ?></th>
                            <td><?= $contractVersionProposal->contract_version !== null
                                ? $this->Html->link(
                                    $contractVersionProposal->contract_version->name,
                                    [
                                        'controller' => 'ContractVersions',
                                        'action' => 'view',
                                        $contractVersionProposal->contract_version_id,
                                    ],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Effective From') ?></th>
                            <td><?= h($contractVersionProposal->effective_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Purpose') ?></th>
                            <td><?= h($contractVersionProposal->purpose->label()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Snapshot Taken') ?></th>
                            <td><?= h($contractVersionProposal->snapshot_taken) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Sent To The Customer') ?></th>
                            <td><?= h($contractVersionProposal->getSending()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Conclusion Date') ?></th>
                            <td><?= h($contractVersionProposal->conclusion_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Carried Over') ?></th>
                            <td><?= h($contractVersionProposal->applied) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Revoked') ?></th>
                            <td><?= h($contractVersionProposal->revoked) ?></td>
                        </tr>
                        <?php if ($contractVersionProposal->terminatesAnotherVersion()) : ?>
                        <tr>
                            <th><?= __('Terminates Contract Version') ?></th>
                            <td><?= $contractVersionProposal->terminated_contract_version !== null
                                ? $this->Html->link(
                                    $contractVersionProposal->terminated_contract_version->name,
                                    [
                                        'controller' => 'ContractVersions',
                                        'action' => 'view',
                                        $contractVersionProposal->terminates_contract_version_id,
                                    ],
                                ) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Number of the contract being terminated') ?></th>
                            <td><?= h($contractVersionProposal->terminated_contract_number) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $contractVersionProposal]) ?>
                </div>
            </div>

            <?= $this->element('ContractVersionProposals/proposed_billings') ?>

            <?php
            // What is billed for is the table above; here is only what the proposal says about the
            // version and the contract themselves, which is usually nothing at all.
            $asksOfRecords = !$changes->version->isEmpty() || !$changes->contract->isEmpty();
            ?>
            <?php if ($asksOfRecords) : ?>
                <h4><?= __('The contract version and the contract') ?></h4>
                <table>
                    <?php foreach ($changes->version->asked() as $field => $value) : ?>
                    <tr>
                        <th><?= h(__('Contract Version') . ' — ' . ProposedVersion::label($field)) ?></th>
                        <td><?= $value === null ? __('cleared') : h($value) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php foreach ($changes->contract->asked() as $field => $value) : ?>
                    <tr>
                        <th><?= h(__('Contract') . ' — ' . ProposedContract::label($field)) ?></th>
                        <td><?= $value === null ? __('cleared') : h($value) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
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

            <?php if (!empty($contractVersionProposal->note)) : ?>
                <h4><?= __('Note') ?></h4>
                <blockquote><?= $this->Text->autoParagraph(h($contractVersionProposal->note)) ?></blockquote>
            <?php endif; ?>
        </div>
    </div>
</div>
