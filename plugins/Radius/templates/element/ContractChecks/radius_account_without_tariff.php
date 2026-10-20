<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __d(
        'radius',
        'Either the billing is missing, or the account should have been deactivated.'
        . ' Where a tariff starts later, the day is given.',
    ) ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($customer_column) : ?>
                    <th><?= __d('radius', 'Customer') ?></th>
                <?php endif ?>
                <?php if ($contract_column) : ?>
                    <th><?= __d('radius', 'Contract') ?></th>
                <?php endif ?>
                <th><?= __d('radius', 'Contract State') ?></th>
                <th><?= __d('radius', 'Username') ?></th>
                <th><?= __d('radius', 'RADIUS User Groups') ?></th>
                <th><?= __d('radius', 'Tariff From') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $contract) : ?>
                <?php
                /** @var list<\Radius\Check\AccountFinding> $findings */
                $findings = $contract->get('radius_findings');
                ?>
                <?php foreach ($findings as $finding) : ?>
                    <tr>
                        <?= $this->element('ContractChecks/customer_cell', [
                            'customer' => $contract->customer,
                            'customer_column' => $customer_column,
                        ]) ?>
                        <?= $this->element('ContractChecks/contract_cell', [
                            'contract' => $contract,
                            'contract_column' => $contract_column,
                        ]) ?>
                        <td><?= h($contract->contract_state?->name) ?></td>
                        <?= $this->element('Radius.ContractChecks/account_cells', ['finding' => $finding]) ?>
                        <td><?= h($finding->tariff_from) ?></td>
                    </tr>
                <?php endforeach ?>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
