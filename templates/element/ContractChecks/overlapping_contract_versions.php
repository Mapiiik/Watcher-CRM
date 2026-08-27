<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $records
 * @var bool|null $contract_column
 */

$contract_column ??= true;
?>
<p>
    <?= __('The version shown is the earlier of the two. While both are in force nothing can say which terms apply.') ?>
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
                <th><?= __('Second One In Force From') ?></th>
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
                    <td>
                        <?php if ($version->valid_until === null) : ?>
                            <?= __('open') ?>
                        <?php else : ?>
                            <?= h($version->valid_until) ?>
                        <?php endif ?>
                    </td>
                    <td><?= h($version->get('overlaps_from')) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
