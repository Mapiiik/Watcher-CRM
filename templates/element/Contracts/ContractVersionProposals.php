<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersionProposal> $contract_version_proposals
 * @var bool|null $version_column Off where the table is already inside one version.
 */

$version_column ??= true;

$whatItAsksFor = function ($proposal): string {
    $changes = $proposal->proposedChanges();
    $lines = count($changes->billings);

    return match (true) {
        $changes->isEmpty() => __('Nothing'),
        $changes->endsTheContract() => __('Ends the contract'),
        $lines > 0 => __n('{0} billing', '{0} billings', $lines, $lines),
        default => __('The contract version'),
    };
};

$whichVersion = function ($proposal): string {
    $version = $proposal->contract_version ?? null;

    return $version === null
        ? ''
        : $version->name;
};
?>
<?php if (!empty($contract_version_proposals)) : ?>
<div class="table-responsive">
    <table>
    <thead>
        <tr>
            <?php if ($version_column) : ?>
                <th><?= __('Contract Version') ?></th>
            <?php endif ?>
            <th><?= __('Effective From') ?></th>
            <th><?= __('Purpose') ?></th>
            <th><?= __('What it asks for') ?></th>
            <th><?= __('Sent To The Customer') ?></th>
            <th><?= __('Conclusion Date') ?></th>
            <th><?= __('State') ?></th>
            <th class="actions"><?= __('Actions') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($contract_version_proposals as $proposal) : ?>
        <tr style="<?= $proposal->isOpen() ? '' : 'color: darkgray;' ?>">
            <?php if ($version_column) : ?>
                <td><?= h($whichVersion($proposal)) ?></td>
            <?php endif ?>
            <td><?= h($proposal->effective_from) ?></td>
            <td><?= h($proposal->purpose->label()) ?></td>
            <td><?= h($whatItAsksFor($proposal)) ?></td>
            <td><?= h($proposal->getSending()) ?></td>
            <td><?= h($proposal->conclusion_date) ?></td>
            <td><?= h($proposal->getState()) ?></td>
            <td class="actions">
                <?php
                // The papers first: that is what a proposal is for, and what somebody is most
                // often after when they find it here. Offered whatever state it is in, because a
                // settled proposal is still the paper that was agreed to.
                ?>
                <?= $this->AuthLink->link(
                    __('Print to PDF'),
                    [
                        'controller' => 'Contracts',
                        'action' => 'print',
                        $proposal->contract_id,
                        '?' => ['proposal_id' => $proposal->id],
                    ],
                ) ?>
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'ContractVersionProposals', 'action' => 'view', $proposal->id],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'ContractVersionProposals', 'action' => 'edit', $proposal->id],
                    ['class' => 'win-link'],
                ) ?>
                <?php if ($proposal->isOpen()) : ?>
                    <?php
                    // Then the steps a proposal goes through, offered where the papers are printed
                    // from - that is where somebody stands when they have just sent them or heard
                    // back.
                    ?>
                    <?php // Carrying over is only offered once there is a signature to carry over. ?>
                    <?= $this->AuthLink->link(
                        $proposal->hasBeenSent()
                            ? __('Record the Sending Again')
                            : __('Record the Sending'),
                        ['controller' => 'ContractVersionProposals', 'action' => 'send', $proposal->id],
                        ['class' => 'win-link'],
                    ) ?>
                    <?= $this->AuthLink->link(
                        $proposal->hasBeenConcluded()
                            ? __('Correct the Signature')
                            : __('Record the Signature'),
                        ['controller' => 'ContractVersionProposals', 'action' => 'conclude', $proposal->id],
                        ['class' => 'win-link'],
                    ) ?>
                    <?php if ($proposal->hasBeenConcluded()) : ?>
                        <?= $this->AuthLink->link(
                            __('Carry Over'),
                            ['controller' => 'ContractVersionProposals', 'action' => 'transfer', $proposal->id],
                            ['class' => 'win-link'],
                        ) ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?= $this->AuthLink->postLink(
                    __('Delete'),
                    ['controller' => 'ContractVersionProposals', 'action' => 'delete', $proposal->id],
                    ['confirm' => __('Are you sure you want to delete # {0}?', $proposal->id)],
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
