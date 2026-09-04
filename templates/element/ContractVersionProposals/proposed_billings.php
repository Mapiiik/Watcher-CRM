<?php
/**
 * What would be billed for if the proposal were carried over, and how to change it.
 *
 * The table is the projection the documents print from, so what the operator reads here is what
 * will be on the paper. Each row says where it comes from - the contract as it stands, or a line
 * the proposal put there - and offers what may be done to it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractVersionProposal $contractVersionProposal
 * @var array<array{billing: \App\Model\Entity\Billing, line: \App\Contracts\Proposal\ProposedBilling|null, ending: bool}> $rows
 * @var bool $mayBeEdited
 */

$proposalId = $contractVersionProposal->id;
?>
<div class="related">
    <?php if ($mayBeEdited) : ?>
        <?= $this->AuthLink->link(
            __('Add a Billing'),
            ['action' => 'billingLine', $proposalId],
            ['class' => 'button button-small float-right win-link'],
        ) ?>
    <?php endif; ?>
    <h4><?= __('What would be billed for') ?></h4>

    <?php if ($rows === []) : ?>
        <p><?= __('Nothing is billed for on this contract.') ?></p>
    <?php else : ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Billing From') ?></th>
                    <th><?= __('Billing Until') ?></th>
                    <th><?= __('Quantity') ?></th>
                    <th><?= __('Total Price') ?></th>
                    <th><?= __('Where it comes from') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row) : ?>
                <?php
                $billing = $row['billing'];
                $line = $row['line'];
                $comesFrom = match (true) {
                    $row['ending'] => __('Stops here'),
                    $line === null => __('As it stands'),
                    $line->isAddition() => __('Added by this proposal'),
                    default => __('Changed by this proposal'),
                };
    ?>
                <tr style="<?= $line === null && !$row['ending'] ? 'color: darkgray;' : '' ?>">
                    <td><?= h($billing->name) ?></td>
                    <td><?= h($billing->billing_from) ?></td>
                    <td><?= h($billing->billing_until) ?></td>
                    <td><?= h($billing->quantity) ?></td>
                    <td><?= h($billing->total_price) ?></td>
                    <td><?= h($comesFrom) ?></td>
                    <td class="actions">
                        <?php if ($mayBeEdited && $line !== null) : ?>
                            <?= $this->AuthLink->link(
                                __('Edit'),
                                ['action' => 'billingLine', $proposalId, $line->id],
                            ) ?>
                            <?= $this->AuthLink->postLink(
                                __('Take Back'),
                                ['action' => 'dropBillingLine', $proposalId, $line->id],
                                ['confirm' => __('Leave this as it stands on the contract?')],
                            ) ?>
                        <?php elseif ($mayBeEdited && !$row['ending']) : ?>
                            <?= $this->AuthLink->link(
                                __('Change'),
                                [
                                    'action' => 'billingLine',
                                    $proposalId,
                                    '?' => ['replaces' => $billing->id],
                                ],
                            ) ?>
                            <?= $this->AuthLink->postLink(
                                __('End'),
                                ['action' => 'endBilling', $proposalId, $billing->id],
                                ['confirm' => __('Stop billing for this?')],
                            ) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
