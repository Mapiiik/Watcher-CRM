<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskState $taskState
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Task State'), ['action' => 'edit', $taskState->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Task State'), ['action' => 'delete', $taskState->id], ['confirm' => __('Are you sure you want to delete # {0}?', $taskState->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Task States'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Task State'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="taskStates view content">
            <h3><?= h($taskState->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($taskState->id) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Name') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($taskState->name)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($taskState->tasks)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Task Type Id') ?></th>
                            <th><?= __('Subject') ?></th>
                            <th><?= __('Text') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th><?= __('Customer Id') ?></th>
                            <th><?= __('Dealer Id') ?></th>
                            <th><?= __('Modified By') ?></th>
                            <th><?= __('Modified') ?></th>
                            <th><?= __('Created By') ?></th>
                            <th><?= __('Created') ?></th>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Phone') ?></th>
                            <th><?= __('Task State Id') ?></th>
                            <th><?= __('Finish Date') ?></th>
                            <th><?= __('Start Date') ?></th>
                            <th><?= __('Estimated Date') ?></th>
                            <th><?= __('Critical Date') ?></th>
                            <th><?= __('Router Id') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($taskState->tasks as $task) : ?>
                        <tr>
                            <td><?= h($task->id) ?></td>
                            <td><?= h($task->task_type_id) ?></td>
                            <td><?= h($task->subject) ?></td>
                            <td><?= h($task->text) ?></td>
                            <td><?= h($task->priority) ?></td>
                            <td><?= h($task->customer_id) ?></td>
                            <td><?= h($task->dealer_id) ?></td>
                            <td><?= h($task->modified_by) ?></td>
                            <td><?= h($task->modified) ?></td>
                            <td><?= h($task->created_by) ?></td>
                            <td><?= h($task->created) ?></td>
                            <td><?= h($task->email) ?></td>
                            <td><?= h($task->phone) ?></td>
                            <td><?= h($task->task_state_id) ?></td>
                            <td><?= h($task->finish_date) ?></td>
                            <td><?= h($task->start_date) ?></td>
                            <td><?= h($task->estimated_date) ?></td>
                            <td><?= h($task->critical_date) ?></td>
                            <td><?= h($task->router_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Tasks', 'action' => 'view', $task->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Tasks', 'action' => 'edit', $task->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Tasks', 'action' => 'delete', $task->id], ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]) ?>
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
