<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskState $taskState
 * @var string[]|\Cake\Collection\CollectionInterface $priorities
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __('Edit Task State'),
                ['action' => 'edit', $taskState->id],
                ['class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Task State'),
                ['action' => 'delete', $taskState->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taskState->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Task States'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Task State'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="taskStates view content">
            <h3><?= h($taskState->name) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($taskState->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Color') ?></th>
                            <td style="background-color: <?= h($taskState->color) ?>;"><?= h($taskState->color) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Completed') ?></th>
                            <td><?= $taskState->completed ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($taskState->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($taskState->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $taskState->has('creator') ? $this->Html->link(
                                $taskState->creator->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $taskState->creator->id,
                                ]
                            ) : h($taskState->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($taskState->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $taskState->has('modifier') ? $this->Html->link(
                                $taskState->modifier->username,
                                [
                                    'plugin' => 'CakeDC/Users',
                                    'controller' => 'Users',
                                    'action' => 'view',
                                    $taskState->modifier->id,
                                ]
                            ) : h($taskState->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($taskState->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Task Type') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th><?= __('Dealer') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Phone') ?></th>
                            <th><?= __('Customer') ?></th>
                            <th><?= __('Customer Number') ?></th>
                            <th><?= __('Access Point') ?></th>
                            <th><?= __('Start Date') ?></th>
                            <th><?= __('Estimated Date') ?></th>
                            <th><?= __('Critical Date') ?></th>
                            <th><?= __('Finish Date') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($taskState->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= $task->has('task_type') ?
                                $this->Html->link(
                                    $task->task_type->name,
                                    ['controller' => 'TaskTypes', 'action' => 'view', $task->task_type->id]
                                ) : '' ?>
                            </td>
                            <td><?= $priorities[$task->priority] ?></td>
                            <td><?= $task->has('dealer') ?
                                $this->Html->link(
                                    $task->dealer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $task->dealer->id]
                                ) : '' ?>
                            </td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= nl2br($task->text ?? '') ?></td>
                            <td><?= h($task->email) ?></td>
                            <td><?= h($task->phone) ?></td>
                            <td><?= $task->has('customer') ?
                                $this->Html->link(
                                    $task->customer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $task->customer->id]
                                ) : '' ?>
                            </td>
                            <td><?= $task->has('customer') ? h($task->customer->number) : '' ?></td>
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
