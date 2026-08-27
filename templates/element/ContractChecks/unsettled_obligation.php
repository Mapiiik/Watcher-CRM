<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __(
        'Either something is owed and nobody has asked for it,'
        . ' or it was waived and nobody has ticked "obligations settled".',
    ) ?>
</p>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <?php if ($contract_column) : ?>
                    <th><?= __('Contract') ?></th>
                <?php endif ?>
                <th><?= __('Valid From') ?></th>
                <th><?= __('Valid Until') ?></th>
                <th><?= __('Date of Termination of Services') ?></th>
                <th><?= __('Obligation Until') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $version) : ?>
                <tr>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $version->contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td><?= h($version->valid_from) ?></td>
                    <td><?= h($version->valid_until) ?></td>
                    <td><?= h($version->contract?->termination_date) ?></td>
                    <td><?= h($version->obligation_until) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
