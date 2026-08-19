<?php
/**
 * The tasks filed under a task state or a task type, as they are listed beside it.
 *
 * Whichever of the two the card is about is left out of the table: it would say the same thing
 * on every row.
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Task> $tasks
 * @var bool|null $task_type_column
 * @var bool|null $task_state_column
 */
?>
<?php if (!empty($tasks)) : ?>
<div class="table-responsive">
    <table>
        <tr>
            <th><?= __d('tasks', 'Number') ?></th>
            <?php if (!empty($task_type_column)) : ?>
            <th><?= __d('tasks', 'Task Type') ?></th>
            <?php endif; ?>
            <?php if (!empty($task_state_column)) : ?>
            <th><?= __d('tasks', 'Task State') ?></th>
            <?php endif; ?>
            <th><?= __d('tasks', 'Priority') ?></th>
            <th><?= __d('tasks', 'User') ?></th>
            <th><?= __d('tasks', 'Subject') ?></th>
            <th><?= __d('tasks', 'Text') ?></th>
            <th><?= __d('tasks', 'Email') ?></th>
            <th><?= __d('tasks', 'Phone') ?></th>
            <th><?= __d('tasks', 'Customer') ?></th>
            <th><?= __d('tasks', 'Customer Number') ?></th>
            <th><?= __d('tasks', 'Access Point') ?></th>
            <th><?= __d('tasks', 'Start Date') ?></th>
            <th><?= __d('tasks', 'Estimated Date') ?></th>
            <th><?= __d('tasks', 'Critical Date') ?></th>
            <th><?= __d('tasks', 'Finish Date') ?></th>
            <th class="actions"><?= __d('tasks', 'Actions') ?></th>
        </tr>
        <?php foreach ($tasks as $task) : ?>
        <tr style="<?= $task->style ?>">
            <td><?= h($task->number) ?></td>
            <?php if (!empty($task_type_column)) : ?>
            <td><?= $task->task_type !== null ?
                $this->Html->link(
                    $task->task_type->name ?? '(' . $task->task_type->id . ')',
                    ['controller' => 'TaskTypes', 'action' => 'view', $task->task_type->id],
                ) : '' ?>
            </td>
            <?php endif; ?>
            <?php if (!empty($task_state_column)) : ?>
            <td><?= $task->task_state !== null ?
                $this->Html->link(
                    $task->task_state->name ?? '(' . $task->task_state->id . ')',
                    ['controller' => 'TaskStates', 'action' => 'view', $task->task_state->id],
                ) : '' ?>
            </td>
            <?php endif; ?>
            <td><?= h($task->getPriorityName()) ?></td>
            <td><?= $task->user !== null ?
                $this->Html->link(
                    $task->user->name ?? '(' . $task->user->id . ')',
                    ['controller' => 'AppUsers', 'action' => 'view', $task->user->id],
                ) : '' ?>
            </td>
            <td><?= h($task->subject) ?></td>
            <td style="overflow-wrap: break-word; max-width: 600px;">
                <?= nl2br(h($task->text ?? '')) ?>
            </td>
            <td><?= h($task->email) ?></td>
            <td><?= h($task->phone) ?></td>
            <td><?= $task->customer !== null ?
                $this->Html->link(
                    $task->customer->name ?? '(' . $task->customer->id . ')',
                    ['controller' => 'Customers', 'action' => 'view', $task->customer->id],
                ) : '' ?>
            </td>
            <td><?= $task->customer !== null ? h($task->customer->number) : '' ?></td>
            <td><?= $task->access_point_name !== null ? h($task->access_point_name) : '' ?></td>
            <td><?= h($task->start_date) ?></td>
            <td><?= h($task->estimated_date) ?></td>
            <td><?= h($task->critical_date) ?></td>
            <td><?= h($task->finish_date) ?></td>
            <td class="actions">
                <?= $this->AuthLink->link(
                    __d('tasks', 'View'),
                    ['controller' => 'Tasks', 'action' => 'view', $task->id],
                ) ?>
                <?= $this->AuthLink->link(
                    __d('tasks', 'Edit'),
                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                    ['class' => 'win-link'],
                ) ?>
                <?= $this->AuthLink->postLink(
                    __d('tasks', 'Delete'),
                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                    [
                        'confirm' => __d(
                            'tasks',
                            'Are you sure you want to delete # {0}?',
                            $task->number,
                        ),
                    ],
                ) ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
