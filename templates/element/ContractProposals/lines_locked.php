<?php
/**
 * Why the lines of a proposal that may still be edited cannot be changed, and the way out where
 * there is one - a notice with its action beside it, the way the work report says work is running.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var bool $mayBeEdited
 * @var \App\Model\Enum\ProposalLinesLock|null $linesLock
 */

$linesLock ??= null;
?>
<?php if ($mayBeEdited && $linesLock !== null) : ?>
    <div class="message warning actions" role="status">
        <?php if ($linesLock->mayBeLifted()) : ?>
            <?= $this->AuthLink->postLink(
                __('Discard Generated Documents'),
                ['controller' => 'ContractProposals', 'action' => 'discardDocuments', $contractProposal->id],
                [
                    'class' => 'float-right',
                    'confirm' => __('Discard the documents generated from this proposal? A copy already'
                        . ' printed will no longer match once the lines change.'),
                ],
            ) ?>
        <?php endif; ?>
        <?= h($linesLock->label()) ?>
    </div>
<?php endif; ?>
