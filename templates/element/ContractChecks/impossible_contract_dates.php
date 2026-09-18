<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

// the subject here is the contract itself, so the column is the record rather than a pointer
$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __('A service cannot be taken away or stopped before it was ever installed.') ?>
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
                <th><?= __('Installation/Establishment Date') ?></th>
                <th><?= __('Uninstallation/Cancellation Date') ?></th>
                <th><?= __('Date of Termination of Services') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $contract) : ?>
                <tr>
                    <?= $this->element('ContractChecks/customer_cell', [
                        'customer' => $contract->customer,
                        'customer_column' => $customer_column,
                    ]) ?>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td><?= h($contract->installation_date) ?></td>
                    <td><?= h($contract->uninstallation_date) ?></td>
                    <td><?= h($contract->termination_date) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
