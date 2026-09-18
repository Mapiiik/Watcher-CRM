<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;
?>
<p>
    <?= __('Not always a fault: a contract can lapse and be signed again. The other reading is a mistyped date.') ?>
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
                <th><?= __('Valid From') ?></th>
                <th><?= __('Valid Until') ?></th>
                <th><?= __('Next One In Force From') ?></th>
                <th><?= __('Days Without a Version') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $version) : ?>
                <?php $until = $version->valid_until ?>
                <?php $resumes = $version->get('resumes_on') ?>
                <tr>
                    <?= $this->element('ContractChecks/customer_cell', [
                        'customer' => $version->contract?->customer,
                        'customer_column' => $customer_column,
                    ]) ?>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $version->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td><?= h($version->valid_from) ?></td>
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
