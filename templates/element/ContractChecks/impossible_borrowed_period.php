<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\BorrowedEquipment> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __(
        'Equipment cannot come back before it went out.'
        . ' Until the day is right it is counted somewhere it is not.',
    ) ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($customer_column) : ?>
                    <th><?= __('Customer') ?></th>
                <?php endif ?>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Equipment Type') ?></th>
                <th><?= __('Serial Number') ?></th>
                <th><?= __('Borrowed From') ?></th>
                <th><?= __('Borrowed Until') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $equipment) : ?>
                <tr>
                    <?= $this->element('ContractChecks/customer_cell', [
                        'customer' => $equipment->contract?->customer,
                        'customer_column' => $customer_column,
                    ]) ?>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $equipment->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td class="dashboard-wrap"><?= h($equipment->equipment_type?->name) ?></td>
                    <td><?= h($equipment->serial_number) ?></td>
                    <td><?= h($equipment->borrowed_from) ?></td>
                    <td><?= h($equipment->borrowed_until) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
