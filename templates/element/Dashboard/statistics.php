<?php
/**
 * This month and the last, side by side.
 *
 * @var \App\View\AppView $this
 * @var list<\Cake\I18n\Date> $months
 * @var \App\Model\Enum\ContractPeriodSource $source
 * @var list<array{title: string, values: list<int>, urls?: list<array<string, mixed>>, signed?: bool}> $rows
 */
?>
<table class="dashboard-table">
    <thead>
        <tr>
            <th></th>
            <?php foreach ($months as $month) : ?>
                <th><?= h($month->i18nFormat('LLLL yyyy')) ?></th>
            <?php endforeach ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row) : ?>
            <tr>
                <td><?= h($row['title']) ?></td>
                <?php foreach ($row['values'] as $i => $value) : ?>
                    <?php $figure = $this->Number->format(
                        $value,
                        ['before' => ($row['signed'] ?? false) && $value > 0 ? '+' : ''],
                    ) ?>
                    <td><?= isset($row['urls'][$i]) ? $this->Html->link($figure, $row['urls'][$i]) : h($figure) ?></td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
<p class="dashboard-hint">
    <?= h($source->label()) ?>.
    <?= __('Active connections today, and on the last day of the month before.') ?>
</p>
