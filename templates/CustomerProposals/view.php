<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\CustomerProposal $customerProposal
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
            <?= $this->AuthLink->link(
                __('Proposal Documents'),
                ['action' => 'documents', $customerProposal->id],
                ['class' => 'side-nav-item'],
            ) ?>
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
            <?= $this->AuthLink->link(
                __('List Proposals'),
                ['action' => 'index'],
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
        </div>
    </aside>
    <div class="column column-90">
        <div class="customerProposals view content">
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
                            <td><?= h($customerProposal->purpose->label()) ?></td>
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
                            <th><?= __('Papers on File') ?></th>
                            <td><?=
                                $this->Html->link(
                                    $ourPages + $theirPages === 0
                                        ? __('None')
                                        : __(
                                            '{0} generated, {1} came back',
                                            $ourPages,
                                            $theirPages,
                                        ),
                                    ['action' => 'documents', $customerProposal->id],
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

            <?php if ($filed !== []) : ?>
                <h4><?= __('Papers on File') ?></h4>
                <h5><?= __('Received Documents') ?></h5>
                <p><?=
                    __(
                        'The papers that came back, whoever signed them. They are filed against the'
                        . ' proposal they answer, so the row says which one that is.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['customerProposal', $customerProposal->id],
                    ['generatedByUs' => false],
                ) ?>
                <h5><?= __('Generated Documents') ?></h5>
                <p><?=
                    __(
                        'What we generated. A document is generated once and handed back'
                        . ' afterwards, so these are the very files the customer was given.',
                    )
                    ?></p>
                <?= $this->cell(
                    'Documents',
                    ['customerProposal', $customerProposal->id],
                    ['generatedByUs' => true],
                ) ?>
            <?php endif; ?>

            <?php if (!empty($customerProposal->note)) : ?>
                <h4><?= __('Note') ?></h4>
                <blockquote><?= $this->Text->autoParagraph(h($customerProposal->note)) ?></blockquote>
            <?php endif; ?>
        </div>
    </div>
</div>
