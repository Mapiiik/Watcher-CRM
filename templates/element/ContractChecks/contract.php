<?php
/**
 * The contract itself, with who holds it, what kind of service it is and where it stands.
 *
 * Shared by the checks whose finding is the contract rather than anything hanging off it -
 * what is missing from it, or what its state and its billing disagree about. They differ in
 * what they are looking for, not in what there is to show afterwards.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;
?>
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
                <th><?= __('Service Type') ?></th>
                <th><?= __('Contract State') ?></th>
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
                    <td><?= h($contract->service_type?->name) ?></td>
                    <td><?= h($contract->contract_state?->name) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
