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
                <?= $this->element('Tasks/related', [
                    'tasks' => $taskState->tasks,
                    'task_type_column' => true,
                ]) ?>
            </div>
        </div>
    </div>
</div>
