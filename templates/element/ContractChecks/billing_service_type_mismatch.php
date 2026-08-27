<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __('Every report that counts what is sold by kind of service counts these under the wrong heading.') ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Service') ?></th>
                <th><?= __('The Service Is For') ?></th>
                <th><?= __('The Contract Is For') ?></th>
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
                    <td><?= h($billing->service?->service_type?->name) ?></td>
                    <td><?= h($billing->contract?->service_type?->name) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
