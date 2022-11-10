<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskType $taskType
 * @var string[]|\Cake\Collection\CollectionInterface $priorities
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Task Type'),
                ['action' => 'edit', $taskType->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Task Type'),
                ['action' => 'delete', $taskType->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taskType->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Task Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Task Type'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="taskTypes view content">
            <h3><?= h($taskType->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Name') ?></th>
                    <td><?= h($taskType->name) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($taskType->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($taskType->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created By') ?></th>
                    <td><?= $taskType->has('creator') ? $this->Html->link(
                        $taskType->creator->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $taskType->creator->id,
                        ]
                    ) : h($taskType->created_by) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($taskType->modified) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified By') ?></th>
                    <td><?= $taskType->has('modifier') ? $this->Html->link(
                        $taskType->modifier->username,
                        [
                            'plugin' => 'CakeDC/Users',
                            'controller' => 'Users',
                            'action' => 'view',
                            $taskType->modifier->id,
                        ]
                    ) : h($taskType->modified_by) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($taskType->tasks)) : ?>
                    <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Priority') ?></th>
                            <th><?= __('Task State') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Phone') ?></th>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Start Date') ?></th>
                            <th><?= __('Estimated Date') ?></th>
                            <th><?= __('Critical Date') ?></th>
                            <th><?= __('Finish Date') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($taskType->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= $priorities[$task->priority] ?></td>
                            <td><?= $task->has('task_state') ?
                                $this->Html->link(
                                    $task->task_state->name,
                                    ['controller' => 'TaskStates', 'action' => 'view', $task->task_state->id]
                                ) : '' ?>
                            </td>
                            <td><?= $task->has('dealer') ?
                                $this->Html->link(
                                    $task->dealer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $task->dealer->id]
                                ) : '' ?>
                            </td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= nl2br($task->text) ?></td>
                            <td><?= h($task->email) ?></td>
                            <td><?= h($task->phone) ?></td>
                            <td><?= $task->has('customer') ?
                                $this->Html->link(
                                    $task->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $task->customer->id]
                                ) : '' ?>
                            </td>
                            <td><?= $task->has('access_point') ? h($task->access_point['name']) : '' ?></td>
                            <td><?= h($task->start_date) ?></td>
                            <td><?= h($task->estimated_date) ?></td>
                            <td><?= h($task->critical_date) ?></td>
                            <td><?= h($task->finish_date) ?></td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __('View'),
                                    ['controller' => 'Tasks', 'action' => 'view', $task->id]
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                                    ['class' => 'win-link']
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]
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
