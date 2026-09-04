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
                <?= $this->AuthLink->link(
                    __('Record That the Papers Went Out'),
                    ['action' => 'send', $contractVersionProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if ($contractVersionProposal->isOpen()) : ?>
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
            <h3><?= __('Proposal') ?></h3>
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
                    <th><?= __('Effective From') ?></th>
                    <td><?= h($contractVersionProposal->effective_from) ?></td>
                </tr>
                <tr>
                    <th><?= __('Snapshot Taken') ?></th>
                    <td><?= h($contractVersionProposal->snapshot_taken) ?></td>
                </tr>
                <tr>
                    <th><?= __('Sent On') ?></th>
                    <td><?= h($contractVersionProposal->sent_date) ?>
                        <?= $contractVersionProposal->sent_by === null
                            ? ''
                            : h(' — ' . $contractVersionProposal->sent_by->label()) ?></td>
                </tr>
                <tr>
                    <th><?= __('Concluded On') ?></th>
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
                    <td><?= h($contractVersionProposal->terminated_contract_version->valid_from ?? '') ?></td>
                </tr>
                <tr>
                    <th><?= __('Number of the contract being terminated') ?></th>
                    <td><?= h($contractVersionProposal->terminated_contract_number) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <?= $this->element('ContractVersionProposals/proposed_billings') ?>

            <h4><?= __('What it asks for') ?></h4>
            <?php if ($changes->isEmpty()) : ?>
                <p><?= __('Nothing. The proposal is the record of the papers, not a change to'
                    . ' anything - which is what a new contract looks like, since its billings'
                    . ' were drawn up before them.') ?></p>
            <?php else : ?>
                <?php foreach ($changes->version->asked() as $field => $value) : ?>
                    <p><?= h(__('Contract version') . ' — ' . $field) ?>:
                        <?= $value === null ? __('cleared') : h($value) ?></p>
                <?php endforeach; ?>

                <?php foreach ($changes->contract->asked() as $field => $value) : ?>
                    <p><?= h(__('Contract') . ' — ' . $field) ?>:
                        <?= $value === null ? __('cleared') : h($value) ?></p>
                <?php endforeach; ?>
            <?php endif; ?>

            <h4><?= __('What was confirmed') ?></h4>
            <?php $answered = $confirmations->toArray(); ?>
            <?php if ($answered === []) : ?>
                <p><?= __('Nothing was asked.') ?></p>
            <?php else : ?>
                <ul>
                <?php foreach (ProposalConfirmations::QUESTIONS as $question) : ?>
                    <?php if (array_key_exists($question, $answered)) : ?>
                        <li><?= h($question) ?>:
                            <?= $answered[$question] ? __('yes') : __('no') ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($contractVersionProposal->note)) : ?>
                <h4><?= __('Note') ?></h4>
                <blockquote><?= $this->Text->autoParagraph(h($contractVersionProposal->note)) ?></blockquote>
            <?php endif; ?>

            <?= $this->element('common/audit', ['entity' => $contractVersionProposal]) ?>
        </div>
    </div>
</div>
