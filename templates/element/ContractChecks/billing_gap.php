<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

// on a contract's own page every row is about that contract, so the column says nothing
$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __(
        'The billing shown is the last one before the break.'
        . ' Either it ends too early, or the one after it starts too late.',
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
                <th><?= __('Service') ?></th>
                <th><?= __('Billing Until') ?></th>
                <th><?= __('Billing Resumes') ?></th>
                <th><?= __('Days Not Billed') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $billing) : ?>
                <?php $until = $billing->billing_until ?>
                <?php $resumes = $billing->get('resumes_on') ?>
                <tr>
                    <?= $this->element('ContractChecks/customer_cell', [
                        'customer' => $billing->contract?->customer,
                        'customer_column' => $customer_column,
                    ]) ?>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $billing->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td class="dashboard-wrap"><?= h($billing->service?->name) ?></td>
                    <td><?= h($until) ?></td>
                    <td><?= h($resumes) ?></td>
                    <td>
                        <?= $until !== null && $resumes !== null
                            ? h((string)($until->diffInDays($resumes) - 1))
                            : '' ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
