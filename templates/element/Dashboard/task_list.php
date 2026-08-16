<?php
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
                        <?php if ($task->customer !== null) : ?>
                            <br><small><?= h($task->customer->name) ?></small>
                        <?php endif ?>
                    </td>
                    <td><?= h($task->getPriorityName()) ?></td>
                    <td><?= $task->critical_date !== null ? h($task->critical_date) : '' ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($total > $shown) : ?>
        <p><?= $this->Html->link(__('and {0} more', $total - $shown), $url) ?></p>
    <?php endif ?>
<?php endif ?>
