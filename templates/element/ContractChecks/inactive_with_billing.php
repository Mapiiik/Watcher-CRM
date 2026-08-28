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
    <?= __('What puts a contract here is the billing running on past the day the contract itself stopped.') ?>
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
                <th><?= __('Contract State') ?></th>
                <th><?= __('Date of Termination of Services') ?></th>
                <th><?= __('Billed Until') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $contract) : ?>
                <tr>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <?php if ($customer_column) : ?>
                        <td class="dashboard-wrap">
                            <?php if ($contract->customer !== null) : ?>
                                <?= $this->Html->link(
                                    $contract->customer->name_for_lists,
                                    ['controller' => 'Customers', 'action' => 'view', $contract->customer->id],
                                ) ?>
                            <?php endif ?>
                        </td>
                    <?php endif ?>
                    <td><?= h($contract->contract_state?->name) ?></td>
                    <td>
                        <?php if ($contract->termination_date === null) : ?>
                            <em><?= __x('termination date', 'None') ?></em>
                        <?php else : ?>
                            <?= h($contract->termination_date) ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($contract->get('billed_open')) : ?>
                            <?= __('open') ?>
                        <?php else : ?>
                            <?= h($contract->get('billed_until')) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
