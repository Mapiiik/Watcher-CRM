<?php
use Cake\I18n\Date;

/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\ContractVersion> $records
 * @var bool|null $contract_column
 * @var bool|null $customer_column
 */

$contract_column ??= true;
$customer_column ??= true;

$today = Date::today();
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
                <?php // whether the papers ever went out is the first thing to know before
                      // chasing somebody about them not coming back ?>
                <th><?= __('Sent To The Customer') ?></th>
                <th><?= __('Conclusion Date') ?></th>
                <?php // which of the two waits a version is past, and so what is about to
                      // happen to it if nobody does anything ?>
                <th><?= __('Standing') ?></th>
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
                        <?php if ($version->sent_date === null) : ?>
                            <em><?= __x('sending date', 'Not recorded') ?></em>
                        <?php else : ?>
                            <?= h($version->sent_date) ?>
                            <?php if ($version->sent_by !== null) : ?>
                                (<?= h($version->sent_by->label()) ?>)
                            <?php endif ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php if ($version->conclusion_date === null) : ?>
                            <em><?= __x('conclusion date', 'None') ?></em>
                        <?php else : ?>
                            <?= h($version->conclusion_date) ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <?php
                        // The deadlines ride along on the day's work only. Read the whole
                        // file and there are none, because there is no deadline on a version
                        // whose start an import left as a day nobody knows.
                        $block_due = $version->has('block_due') ? $version->block_due : null;
                        $notify_due = $version->has('notify_due') ? $version->notify_due : null;
                        ?>
                        <?php if ($block_due !== null && $block_due <= $today) : ?>
                            <strong style="color: red;"><?= __('Due to be cut off') ?></strong>
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
