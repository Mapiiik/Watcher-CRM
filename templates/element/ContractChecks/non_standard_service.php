<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Billing> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __(
        'Not a fault on its own. Where the same thing is written by hand again and again,'
        . ' it is a service the price list is still missing.',
    ) ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Service') ?></th>
                <th><?= __('Text') ?></th>
                <th><?= __('Price') ?></th>
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
                    <td class="dashboard-wrap">
                        <?php if ($billing->service === null) : ?>
                            <em><?= __x('service', 'None') ?></em>
                        <?php else : ?>
                            <?= h($billing->service->name) ?>
                        <?php endif ?>
                    </td>
                    <td class="dashboard-wrap"><?= h($billing->text) ?></td>
                    <td><?= h($billing->price) ?></td>
                    <td><?= h($billing->billing_from) ?></td>
                    <td><?= h($billing->billing_until) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
