<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskType $taskType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Task Type'), ['action' => 'edit', $taskType->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Task Type'), ['action' => 'delete', $taskType->id], ['confirm' => __('Are you sure you want to delete # {0}?', $taskType->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Task Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Task Type'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="taskTypes view content">
            <h3><?= h($taskType->name) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($taskType->id) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Name') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($taskType->name)); ?>
                </blockquote>
            </div>
            <div class="related">
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($taskType->tasks)) : ?>
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
                        <?php foreach ($taskType->tasks as $tasks) : ?>
                        <tr>
                            <td><?= h($tasks->id) ?></td>
                            <td><?= h($tasks->task_type_id) ?></td>
                            <td><?= h($tasks->subject) ?></td>
                            <td><?= h($tasks->text) ?></td>
                            <td><?= h($tasks->priority) ?></td>
                            <td><?= h($tasks->customer_id) ?></td>
                            <td><?= h($tasks->dealer_id) ?></td>
                            <td><?= h($tasks->modified_by) ?></td>
                            <td><?= h($tasks->modified) ?></td>
                            <td><?= h($tasks->created_by) ?></td>
                            <td><?= h($tasks->created) ?></td>
                            <td><?= h($tasks->email) ?></td>
                            <td><?= h($tasks->phone) ?></td>
                            <td><?= h($tasks->task_state_id) ?></td>
                            <td><?= h($tasks->finish_date) ?></td>
                            <td><?= h($tasks->start_date) ?></td>
                            <td><?= h($tasks->estimated_date) ?></td>
                            <td><?= h($tasks->critical_date) ?></td>
                            <td><?= h($tasks->router_id) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Tasks', 'action' => 'view', $tasks->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Tasks', 'action' => 'edit', $tasks->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Tasks', 'action' => 'delete', $tasks->id], ['confirm' => __('Are you sure you want to delete # {0}?', $tasks->id)]) ?>
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
