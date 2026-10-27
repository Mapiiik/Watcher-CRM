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
    <?= __(
        'A version is concluded not long before or after the day it takes effect, never after it ends'
        . ' and never ahead of today. A day outside that is usually a mistyped year.',
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
                <th><?= __('Valid From') ?></th>
                <th><?= __('Valid Until') ?></th>
                <th><?= __('Conclusion Date') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $version) : ?>
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
                    <td><?= h($version->valid_until) ?></td>
                    <td><?= h($version->conclusion_date) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
