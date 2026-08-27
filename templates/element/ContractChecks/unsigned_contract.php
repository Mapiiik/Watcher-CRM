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
    <?= __('Either nothing says when it was concluded, or what says so is from long before it took effect.') ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <?php if ($customer_column) : ?>
                    <th><?= __('Customer') ?></th>
                <?php endif ?>
                <th><?= __('Valid From') ?></th>
                <th><?= __('Conclusion Date') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $version) : ?>
                <tr>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $version->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <?php if ($customer_column) : ?>
                        <td class="dashboard-wrap">
                            <?php if ($version->contract?->customer !== null) : ?>
                                <?= $this->Html->link(
                                    $version->contract->customer->name_for_lists,
                                    ['controller' => 'Customers', 'action' => 'view', $version->contract->customer->id],
                                ) ?>
                            <?php endif ?>
                        </td>
                    <?php endif ?>
                    <td><?= h($version->valid_from) ?></td>
                    <td>
                        <?php if ($version->conclusion_date === null) : ?>
                            <em><?= __x('conclusion date', 'None') ?></em>
                        <?php else : ?>
                            <?= h($version->conclusion_date) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
