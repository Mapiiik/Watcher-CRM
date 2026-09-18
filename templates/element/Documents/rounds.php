<?php
/**
 * Every round in view, one row each.
 *
 * Both pages draw this: the listing wants to know what state things are in, and the workbench
 * wants the same table with somewhere to go from each row. A proposal is put to the customer, so
 * the contracts it is about are named on its own row rather than in a column of their own.
 *
 * @var \App\View\AppView $this
 * @var array<array<string, mixed>> $rounds
 * @var bool $showCustomer Whether the rows say whose they are.
 * @var bool $working Whether the row offers what may be done with the round.
 * @var bool $paged Whether the page pages through these, which is what makes a column sortable.
 */

use App\Model\Enum\ProposalStep;

$working = $working ?? false;
$paged = $paged ?? false;

/**
 * A column heading, which the pager turns into a way of ordering the listing.
 *
 * @param string $field The column behind it.
 * @param string $said What to call it.
 * @return string
 */
$heading = function (string $field, string $said) use ($paged): string {
    return $paged ? $this->Paginator->sort($field, $said) : h($said);
};
?>
<?php if ($rounds === []) : ?>
    <p><?= __('No documents have been drawn up here yet.') ?></p>
<?php else : ?>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($showCustomer) : ?>
                <th><?= __('Customer') ?></th>
                <?php endif; ?>
                <th><?= $heading('CustomerProposals.effective_from', __('Proposal')) ?></th>
                <th><?= __('State') ?></th>
                <th><?= $heading('CustomerProposals.sent_date', __('Sent')) ?></th>
                <th><?= $heading('CustomerProposals.conclusion_date', __('Signed')) ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rounds as $row) : ?>
                <?php
                $one = $row['round'];

                // A step is taken on the proposal and reaches everything in it, so a part of one
                // offers none of its own.
                $itsOwnSteps = $row['agenda'] === 'CustomerProposals';

                // The version is what a proposal is about, and it is worth a way through to it -
                // built here so the cell below stays one line.
                $versionCell = $row['version'] === null ? '' : '<br><small>' . $this->Html->link(
                    (string)$row['version']->name,
                    [
                        'controller' => 'ContractVersions',
                        'action' => 'view',
                        $row['version']->id,
                    ],
                ) . '</small>';

                // What a proposal is about, said on its own row: the parts are seen by opening it,
                // so the listing has to say this much by itself.
                $coversCell = ($row['covers'] ?? []) === [] ? '' : '<br><small>' . h(__(
                    'Contracts: {0}',
                    implode(', ', $row['covers']),
                )) . '</small>';

                // A round nobody has anything left to do about is read rather than worked on, so
                // it steps back - which is not the same as settled, because what it holds may
                // still be waiting to be carried over.
                ?>
            <tr style="<?= $one->hasBeenDealtWith() ? 'color: darkgray;' : '' ?>">
                <?php if ($showCustomer) : ?>
                <td><?=
                    $row['customer'] === null ? '' : $this->Html->link(
                        (string)$row['customer']->number,
                        [
                            'controller' => 'Customers',
                            'action' => 'view',
                            $row['customer']->id,
                        ],
                    )
                    ?></td>
                <?php endif; ?>
                <td>
                    <?= h($row['purpose']) ?>
                    <br><small><?= h($one->effective_from) ?></small>
                    <?= $versionCell ?>
                    <?= $coversCell ?>
                </td>
                <td><?= h($one->getState()) ?></td>
                <td><?= h($one->getSending()) ?></td>
                <td><?= h($one->conclusion_date) ?></td>
                <td class="actions">
                    <?php if ($working) : ?>
                        <?= $this->Html->link(__('Documents'), [
                            'action' => 'manage',
                            '?' => ['proposal_id' => $one->id, 'agenda' => $row['agenda']],
                        ]) ?>
                        <?= $this->AuthLink->link(__('View'), [
                            'controller' => $row['agenda'],
                            'action' => 'view',
                            $one->id,
                        ]) ?>
                        <?php if ($itsOwnSteps) : ?>
                            <?php if ($one->isDueFor(ProposalStep::Delivered)) : ?>
                                <?= $this->AuthLink->link(__('Record the Sending'), [
                                    'controller' => 'CustomerProposals',
                                    'action' => 'send',
                                    $one->id,
                                ]) ?>
                            <?php endif; ?>
                            <?php if ($one->isDueFor(ProposalStep::Signed)) : ?>
                                <?= $this->AuthLink->link(__('Record the Signature'), [
                                    'controller' => 'CustomerProposals',
                                    'action' => 'conclude',
                                    $one->id,
                                ]) ?>
                            <?php endif; ?>
                            <?php if ($one->hasSomethingToCarryOver()) : ?>
                                <?= $this->AuthLink->link(__('Carry Over'), [
                                    'controller' => 'CustomerProposals',
                                    'action' => 'transfer',
                                    $one->id,
                                ]) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <?= $this->Html->link(__('Documents'), [
                            'action' => 'manage',
                            'customer_id' => $row['customer']?->id,
                            'contract_id' => $row['contract']?->id,
                            '?' => ['proposal_id' => $one->id, 'agenda' => $row['agenda']],
                        ]) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
