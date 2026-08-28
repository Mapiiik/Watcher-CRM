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
        'The billing shown is the earlier of the two. Usually it was left open when the next one'
        . ' was written - and two tariffs at once are an overlap however differently they are named.',
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
                <th><?= __('Billing From') ?></th>
                <th><?= __('Billing Until') ?></th>
                <th><?= __('Billed Twice From') ?></th>
                <th><?= __('Overlaps With') ?></th>
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
                    <td>
                        <?php if ($billing->billing_until === null) : ?>
                            <?= __('open') ?>
                        <?php else : ?>
                            <?= h($billing->billing_until) ?>
                        <?php endif ?>
                    </td>
                    <td><?= h($billing->get('overlaps_from')) ?></td>
                    <td class="dashboard-wrap"><?= h($billing->get('overlaps_with')) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
