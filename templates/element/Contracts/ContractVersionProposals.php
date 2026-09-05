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
                <?= $this->AuthLink->link(
                    __('View'),
                    ['controller' => 'ContractVersionProposals', 'action' => 'view', $proposal->id],
                ) ?>
                <?= $this->AuthLink->link(
                    __('Edit'),
                    ['controller' => 'ContractVersionProposals', 'action' => 'edit', $proposal->id],
                    ['class' => 'win-link'],
                ) ?>
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
