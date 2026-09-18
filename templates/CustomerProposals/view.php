<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
 * @var array<array<string, mixed>> $parts
 * @var bool $mayBeEdited
 * @var bool $mayBeDeleted
 * @var array<string, array<string, list<\Files\Model\Entity\FileLink>>> $filed
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
                    ['action' => 'edit', $customerProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if ($customerProposal->isOpen()) : ?>
                <?= $this->AuthLink->link(
                    $customerProposal->hasBeenSent()
                        ? __('Record the Sending Again')
                        : __('Record the Sending'),
                    ['action' => 'send', $customerProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if (!$customerProposal->hasBeenRevoked()) : ?>
                <?= $this->AuthLink->link(
                    $customerProposal->hasBeenConcluded()
                        ? __('Correct the Signature')
                        : __('Record the Signature'),
                    ['action' => 'conclude', $customerProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if ($customerProposal->hasChangesToApply()) : ?>
                <?= $this->AuthLink->link(
                    __('Carry Over'),
                    ['action' => 'applyChanges', $customerProposal->id],
                    ['class' => 'side-nav-item'],
                ) ?>
            <?php endif; ?>
            <?php if ($customerProposal->isOpen()) : ?>
                <?= $this->AuthLink->postLink(
                    __('Revoke'),
                    ['action' => 'revoke', $customerProposal->id],
                    [
                        'class' => 'side-nav-item',
                        'confirm' => __('Give up on this round of papers?'),
                    ],
                ) ?>
            <?php endif; ?>
            <?php if ($mayBeDeleted) : ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['action' => 'delete', $customerProposal->id],
                    ['class' => 'side-nav-item', 'confirm' => __('Are you sure?')],
                ) ?>
            <?php endif; ?>
            <br>
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => ['proposal_id' => $customerProposal->id, 'agenda' => 'CustomerProposals'],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals view content">
            <?= $this->AuthLink->link(
                __('Documents'),
                [
                    'plugin' => null,
                    'controller' => 'Documents',
                    'action' => 'manage',
                    '?' => ['proposal_id' => $customerProposal->id, 'agenda' => 'CustomerProposals'],
                ],
                ['class' => 'button float-right'],
            ) ?>
            <?= $this->element('CustomerProposals/heading') ?>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $this->Html->link(
                                h($customerProposal->customer->name ?? ''),
                                [
                                    'controller' => 'Customers',
                                    'action' => 'view',
                                    $customerProposal->customer_id,
                                ],
                            ) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Purpose') ?></th>
                            <td><?= h($customerProposal->whatItIsFor()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Effective From') ?></th>
                            <td><?= h($customerProposal->effective_from) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Sent To The Customer') ?></th>
                            <td><?= h($customerProposal->getSending()) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Conclusion Date') ?></th>
                            <td><?= h($customerProposal->conclusion_date) ?></td>
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
                                            'proposal_id' => $customerProposal->id,
                                            'agenda' => 'CustomerProposals',
                                        ],
                                    ],
                                )
                                ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Revoked') ?></th>
                            <td><?= h($customerProposal->revoked) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $customerProposal]) ?>
                </div>
            </div>
            <?php
            // Standing at the foot of the card, the button floats out of it unless the card is
            // given something to grow to.
            $another = $this->AuthLink->link(
                __('New Contract Proposal'),
                [
                    'controller' => 'ContractProposals',
                    'action' => 'add',
                    '?' => ['proposal_id' => $customerProposal->id],
                ],
                ['class' => 'button button-small float-right win-link'],
            );
            ?>
            <?= $this->Html->div('clearfix', $another) ?>
        </div>
        <?php foreach ($parts as $part) : ?>
        <br>
        <div class="customerProposals view content">
            <?php
            $saidOf = $part['papers']->getName();
            ?>
            <h4><?= h($saidOf) ?></h4>
            <p><?=
                $this->Html->link(
                    __('What these papers say'),
                    [
                        'controller' => 'ContractProposals',
                        'action' => 'view',
                        $part['papers']->id,
                    ],
                )
                ?></p>
            <?= $this->element('ContractProposals/what_it_says', [
                'contractProposal' => $part['papers'],
                'rows' => $part['rows'],
                'planned' => $part['planned'],
                'confirmations' => $part['confirmations'],
                'mayBeEdited' => $part['mayBeEdited'],
                ]) ?>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($customerProposal->note)) : ?>
        <br>
        <div class="customerProposals view content">
            <h4><?= __('Note') ?></h4>
            <blockquote><?= $this->Text->autoParagraph(h($customerProposal->note)) ?></blockquote>
        </div>
        <?php endif; ?>
    </div>
</div>
