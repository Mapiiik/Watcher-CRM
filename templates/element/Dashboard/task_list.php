<?php
use App\Model\Entity\Task;

/**
 * The rows every task card is drawn as.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Task> $tasks
 * @var int $total
 * @var string $empty
 * @var array<string, mixed> $url
 */

$shown = 0;

// The row already carries the colour of the task's state, so a pressing priority is marked
// out as a badge of its own rather than by tinting the cell, which would have to win against
// whatever colour the row happens to be.
$priorityClass = function (Task $task): string {
    return match (true) {
        $task->priority >= Task::PRIORITY_URGENT => 'dashboard-priority-urgent',
        $task->priority >= Task::PRIORITY_HIGH => 'dashboard-priority-high',
        default => 'dashboard-priority-plain',
    };
};
?>
<?php if ($total === 0) : ?>
    <p><?= h($empty) ?></p>
<?php else : ?>
    <table class="dashboard-table">
        <tbody>
            <?php foreach ($tasks as $task) : ?>
                <?php $shown++ ?>
                <tr style="<?= $task->style ?>">
                    <td>
                        <?= $this->Html->link(
                            $task->subject ?? $task->task_type->name ?? $task->number,
                            ['controller' => 'Tasks', 'action' => 'view', $task->id],
                        ) ?>
                        <?php $summary = $task->getSummaryText(false) ?>
                        <?php if ($summary !== '') : ?>
                            <br><small class="dashboard-hint" title="<?= h($summary) ?>">
                                <?= h($summary) ?>
                            </small>
                        <?php endif ?>
                    </td>
                    <td>
                        <span class="dashboard-priority <?= $priorityClass($task) ?>">
                            <?= h($task->getPriorityName()) ?>
                        </span>
                        <?php if ($task->critical_date !== null) : ?>
                            <br><?= h($task->critical_date) ?>
                        <?php elseif ($task->estimated_date !== null) : ?>
                            <?php // in brackets and muted, as an estimate slipping is a
                                  // softer thing than a promise being broken ?>
                            <br><span
                                class="dashboard-date-estimated"
                                title="<?= h(__('Estimated Date')) ?>"
                            >(<?= h($task->estimated_date) ?>)</span>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($total > $shown) : ?>
        <p><?= $this->Html->link(__('and {0} more', $total - $shown), $url) ?></p>
    <?php endif ?>
<?php endif ?>
