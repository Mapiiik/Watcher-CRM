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
        'The router takes only one of the addresses, so the customer may be given another one than meant.'
            . ' Leave the contract only the address it should have, the account follows it.',
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
                <th><?= __d('radius', 'Username') ?></th>
                <th><?= __d('radius', 'RADIUS User Groups') ?></th>
                <th><?= __d('radius', 'IP Addresses') ?></th>
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
                        <?= $this->element('Radius.ContractChecks/account_cells', ['finding' => $finding]) ?>
                        <td><?= implode('<br>', array_map(h(...), $finding->competing_addresses)) ?></td>
                    </tr>
                <?php endforeach ?>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
