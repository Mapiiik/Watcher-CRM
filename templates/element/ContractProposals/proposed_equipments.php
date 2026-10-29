<?php
/**
 * The borrowed equipments as the proposal would leave them, and how to change them.
 *
 * Drawn the way the billing lines beside it are. What the snapshot holds may be ended where the
 * proposal replaces a version, and anything may be added. The papers print from the same lines,
 * so the table reads what they will say.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ContractProposal $contractProposal
 * @var array<array{equipment: \App\Model\Entity\BorrowedEquipment, line: \App\Contracts\Proposal\ProposedEquipment|null}> $equipmentRows
 * @var bool $mayBeEdited
 */

$proposalId = $contractProposal->id;
$movesEquipment = $contractProposal->purpose->movesEquipment();
$mayEnd = $contractProposal->mayTakeEquipmentBack();
?>
<?php if ($movesEquipment || $equipmentRows !== []) : ?>
<div class="related">
    <?php if ($mayBeEdited && $movesEquipment) : ?>
        <?= $this->AuthLink->link(
            __('Add a Borrowed Equipment'),
            ['controller' => 'ContractProposals', 'action' => 'equipmentLine', $proposalId],
            ['class' => 'button button-small float-right win-link'],
        ) ?>
    <?php endif; ?>
    <h4><?= __('Borrowed Equipments after the change') ?></h4>

    <?php if ($equipmentRows === []) : ?>
        <p><?= __('No equipment is borrowed on this contract.') ?></p>
    <?php else : ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= __('Equipment Type') ?></th>
                    <th><?= __('Serial Number') ?></th>
                    <th><?= __('Source') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($equipmentRows as $row) : ?>
                <?php
                $equipment = $row['equipment'];
                $line = $row['line'];
                ?>
                <tr style="<?= $line === null ? 'color: darkgray;' : '' ?>">
                    <td><?= h($equipment->equipment_type->name ?? '') ?></td>
                    <td><?= h($equipment->serial_number) ?></td>
                    <td><?= h($line === null ? __('As it stands') : $line->move->label()) ?></td>
                    <td class="actions">
                        <?php if ($mayBeEdited && $line !== null) : ?>
                            <?php if ($line->lends()) : ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    [
                                        'controller' => 'ContractProposals',
                                        'action' => 'equipmentLine',
                                        $proposalId,
                                        $line->id,
                                    ],
                                    ['class' => 'win-link'],
                                ) ?>
                            <?php endif; ?>
                            <?= $this->AuthLink->postLink(
                                __('Take Back'),
                                [
                                    'controller' => 'ContractProposals',
                                    'action' => 'dropEquipmentLine',
                                    $proposalId,
                                    $line->id,
                                ],
                                ['confirm' => __('Leave this as it is on the contract?')],
                            ) ?>
                        <?php elseif ($mayBeEdited && $mayEnd) : ?>
                            <?= $this->AuthLink->postLink(
                                __('End'),
                                [
                                    'controller' => 'ContractProposals',
                                    'action' => 'returnEquipment',
                                    $proposalId,
                                    $equipment->id,
                                ],
                                ['confirm' => __('End this borrowed equipment?')],
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
<?php endif; ?>
