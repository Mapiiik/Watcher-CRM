<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __('A billing that ends before it begins invoices nothing at all.') ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Service') ?></th>
                <th><?= __('Billing From') ?></th>
                <th><?= __('Billing Until') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $billing) : ?>
                <tr>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $billing->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td class="dashboard-wrap"><?= h($billing->service?->name) ?></td>
                    <td><?= h($billing->billing_from) ?></td>
                    <td><?= h($billing->billing_until) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
