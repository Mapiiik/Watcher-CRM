<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskState $taskState
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('tasks', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('tasks', 'Edit Task State'),
                ['action' => 'edit', $taskState->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('tasks', 'Delete Task State'),
                ['action' => 'delete', $taskState->id],
                [
                    'confirm' => __d('tasks', 'Are you sure you want to delete # {0}?', $taskState->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __d('tasks', 'List Task States'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('tasks', 'New Task State'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskStates view content">
            <h3><?= h($taskState->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('tasks', 'Name') ?></th>
                            <td><?= h($taskState->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('tasks', 'Color') ?></th>
                            <td style="background-color: <?= h($taskState->color) ?>;"><?= h($taskState->color) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('tasks', 'Priority') ?></th>
                            <td><?= h($taskState->priority) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('tasks', 'Completed') ?></th>
                            <td><?= $taskState->completed ? __d('tasks', 'Yes') : __d('tasks', 'No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $taskState]) ?>
                </div>
            </div>
            <div class="related">
                <h4><?= __d('tasks', 'Related Tasks') ?></h4>
                <?php if (!empty($taskState->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('tasks', 'Number') ?></th>
                            <th><?= __d('tasks', 'Task Type') ?></th>
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
                        <?php foreach ($taskState->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= h($task->number) ?></td>
                            <td><?= $task->task_type !== null ?
                                $this->Html->link(
                                    $task->task_type->name ?? '(' . $task->task_type->id . ')',
                                    ['controller' => 'TaskTypes', 'action' => 'view', $task->task_type->id],
                                ) : '' ?>
                            </td>
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
            </div>
        </div>
    </div>
</div>
