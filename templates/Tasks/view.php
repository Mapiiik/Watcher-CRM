<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Task $task
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Task'), ['action' => 'edit', $task->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Task'), ['action' => 'delete', $task->id], ['confirm' => __('Are you sure you want to delete # {0}?', $task->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Tasks'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Task'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="tasks view content">
            <h3><?= h($task->id) ?></h3>
            <div class="row">
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Task Type') ?></th>
                            <td><?= $task->has('task_type') ? $this->Html->link($task->task_type->name, ['controller' => 'TaskTypes', 'action' => 'view', $task->task_type->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Priority') ?></th>
                            <td><?= $this->Number->format($task->priority) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Task State') ?></th>
                            <td><?= $task->has('task_state') ? $this->Html->link($task->task_state->name, ['controller' => 'TaskStates', 'action' => 'view', $task->task_state->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Subject') ?></th>
                            <td><?= h($task->subject) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Email') ?></th>
                            <td><?= h($task->email) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Phone') ?></th>
                            <td><?= h($task->phone) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer') ?></th>
                            <td><?= $task->has('customer') ? $this->Html->link($task->customer->name, ['controller' => 'Customers', 'action' => 'view', $task->customer->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Dealer') ?></th>
                            <td><?= $task->has('dealer') ? $this->Html->link($task->dealer->name, ['controller' => 'Customers', 'action' => 'view', $task->dealer->id]) : '' ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Router') ?></th>
                            <td><?= $task->has('router') ? $this->Html->link($task->router->name, ['controller' => 'Routers', 'action' => 'view', $task->router->id]) : '' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column-responsive">
                    <table>
                        <tr>
                            <th><?= __('Start Date') ?></th>
                            <td><?= h($task->start_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Estimated Date') ?></th>
                            <td><?= h($task->estimated_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Critical Date') ?></th>
                            <td><?= h($task->critical_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Finish Date') ?></th>
                            <td><?= h($task->finish_date) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <td><?= $this->Number->format($task->id) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created') ?></th>
                            <td><?= h($task->created) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Created By') ?></th>
                            <td><?= $this->Number->format($task->created_by) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified') ?></th>
                            <td><?= h($task->modified) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Modified By') ?></th>
                            <td><?= $this->Number->format($task->modified_by) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="text">
                <strong><?= __('Text') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($task->text)); ?>
                </blockquote>
            </div>
        </div>
    </div>
</div>
