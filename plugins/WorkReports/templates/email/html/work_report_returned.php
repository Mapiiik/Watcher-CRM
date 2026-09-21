<?php
/**
 * A report returned to its worker to be corrected.
 *
 * @var \App\View\AppView $this
 * @var string $title
 * @var \WorkReports\Model\Entity\WorkReport $workReport
 */

$this->assign('title', $title);
?>
<h2><?= $this->fetch('title') ?></h2>
<table>
    <tr>
        <th><?= __d('work_reports', 'Returned By') ?></th>
        <td><?= h($workReport->returner?->name) ?></td>
    </tr>
    <tr>
        <th><?= __d('work_reports', 'Returned') ?></th>
        <td><?= h($workReport->returned) ?></td>
    </tr>
</table>
<div class="text">
    <strong><?= __d('work_reports', 'Return Reason') ?></strong>
    <blockquote>
        <?= $this->Text->autoParagraph(h((string)$workReport->return_reason)); ?>
    </blockquote>
</div>
<?= $this->Html->link(
    __d('work_reports', 'View Work Report'),
    [
        'plugin' => 'WorkReports',
        'controller' => 'WorkReports',
        'action' => 'sheet',
        '?' => ['user_id' => $workReport->user_id, 'month' => $workReport->month->format('Y-m')],
        '_full' => true,
    ],
) ?>
