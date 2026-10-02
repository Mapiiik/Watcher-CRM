<?php
use Cake\I18n\Date;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Contract> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;

$today = Date::today();
?>
<p>
    <?= __('The service is being provided and charged for, and no contract version covers today.') ?>
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
                <?php // since when the service is charged for, which is what the wait is counted
                      // from where there is no version to count from ?>
                <th><?= __('Charged Since') ?></th>
                <th><?= __('Installation Date') ?></th>
                <?php // empty where no version was ever drawn, a date where the last one ran out ?>
                <th><?= __('Covered Until') ?></th>
                <th><?= __('Deadline') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $contract) : ?>
                <tr>
                    <?= $this->element('ContractChecks/customer_cell', [
                        'customer' => $contract->customer,
                        'customer_column' => $customer_column,
                    ]) ?>
                    <?= $this->element('ContractChecks/contract_cell', [
                        'contract' => $contract,
                        'contract_column' => $contract_column,
                    ]) ?>
                    <td><?= h($contract->has('charged_since') ? $contract->get('charged_since') : null) ?></td>
                    <td>
                        <?php if ($contract->installation_date === null) : ?>
                            <em><?= __x('installation date', 'None') ?></em>
                        <?php else : ?>
                            <?= h($contract->installation_date) ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php $covered = $contract->has('covered_until') ? $contract->get('covered_until') : null; ?>
                        <?php if ($covered === null) : ?>
                            <em><?= __('No version ever drawn') ?></em>
                        <?php else : ?>
                            <?= h($covered) ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php
                        // The deadlines ride along on the day's work only; on the whole file they
                        // come back empty, because nothing is coming for what the office is not
                        // watching.
                        $block_due = $contract->has('block_due') ? $contract->block_due : null;
                        $notify_due = $contract->has('notify_due') ? $contract->notify_due : null;
                        ?>
                        <?php if ($block_due !== null && $block_due <= $today) : ?>
                            <strong class="error-text"><?= __('Due to be cut off') ?></strong>
                        <?php elseif ($notify_due !== null && $notify_due <= $today) : ?>
                            <?= __('Due a reminder') ?>
                        <?php elseif ($notify_due !== null) : ?>
                            <?= __('In time until {0}', h($notify_due)) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
