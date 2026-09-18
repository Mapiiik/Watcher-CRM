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

use App\Model\Enum\DocumentVariant;

$ourPages = 0;
$theirPages = 0;
foreach ($filed as $byVariant) {
    foreach ($byVariant as $variant => $links) {
        if (DocumentVariant::tryFrom((string)$variant)?->isGeneratedByUs() ?? false) {
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
            <?php endif; ?>
            <?php
            // Sending, signing and carrying over happen on the proposal and reach everything in
            // it, so they are not offered here - the way up to them is.
            ?>
            <?= $this->AuthLink->link(
                __('The Proposal These Are Part Of'),
                [
                    'plugin' => null,
                    'controller' => 'CustomerProposals',
                    'action' => 'view',
                    $contractProposal->customer_proposal_id,
                ],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => ['proposal_id' => $contractProposal->id, 'agenda' => 'ContractProposals'],
                ],
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
        </div>
    </aside>
    <div class="column column-90">
        <div class="contractProposals view content">
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => ['proposal_id' => $contractProposal->id, 'agenda' => 'ContractProposals'],
                ],
                ['class' => 'button float-right'],
            ) ?>
            <?= $this->element('ContractProposals/heading') ?>
            <?php if ($contractProposal->customer_proposal !== null) : ?>
                <?php
                // Papers of a contract are a part of a proposal put to the customer, and the way
                // back up to it is said where the papers are read rather than only in the menu.
                $round = $contractProposal->customer_proposal;
                $itGoesOutIn = $this->Html->link(
                    __('{0} from {1}', [$round->whatItIsFor(), $round->effective_from]),
                    ['controller' => 'CustomerProposals', 'action' => 'view', $round->id],
                );
                ?>
                <p><?= __('These papers go out in {0}', $itGoesOutIn) ?></p>
                <br>
            <?php endif; ?>
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
                            <th><?= __('Documents on File') ?></th>
                            <td><?=
                                $this->Html->link(
                                    $ourPages + $theirPages === 0
                                        ? __('None')
                                        : __(
                                            '{0} generated, {1} came back',
                                            $ourPages,
                                            $theirPages,
                                        ),
                                    [
                                        'plugin' => null,
                                        'controller' => 'Documents',
                                        'action' => 'manage',
                                        '?' => [
                                            'proposal_id' => $contractProposal->id,
                                            'agenda' => 'ContractProposals',
                                        ],
                                    ],
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

            <?= $this->element('ContractProposals/what_it_says') ?>

            <?php if (!empty($contractProposal->note)) : ?>
                <h4><?= __('Note') ?></h4>
                <blockquote><?= $this->Text->autoParagraph(h($contractProposal->note)) ?></blockquote>
            <?php endif; ?>
        </div>
    </div>
</div>
