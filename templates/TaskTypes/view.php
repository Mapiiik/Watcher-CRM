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
            <?= $this->AuthLink->link(
                __('Edit Task Type'),
                ['action' => 'edit', $taskType->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __('Delete Task Type'),
                ['action' => 'delete', $taskType->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $taskType->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Task Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->AuthLink->link(__('New Task Type'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskTypes view content">
            <h3><?= h($taskType->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __('Name') ?></th>
                            <td><?= h($taskType->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Customer Required') ?></th>
                            <td><?= $taskType->customer_required ? __('Yes') : __('No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __('Contract Required') ?></th>
                            <td><?= $taskType->contract_required ? __('Yes') : __('No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $taskType]) ?>
                </div>
            </div>
            <div class="related">
                <h4><?= __('Related Tasks') ?></h4>
                <?php if (!empty($taskType->tasks)) : ?>
                    <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Number') ?></th>
                            <th><?= __('Priority') ?></th>
                            <th><?= __('Task State') ?></th>
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
                        <?php foreach ($taskType->tasks as $task) : ?>
                        <tr style="<?= $task->style ?>">
                            <td><?= h($task->number) ?></td>
                            <td><?= h($task->getPriorityName()) ?></td>
                            <td><?= $task->task_state !== null ?
                                $this->Html->link(
                                    $task->task_state->name,
                                    ['controller' => 'TaskStates', 'action' => 'view', $task->task_state->id],
                                ) : '' ?>
                            </td>
                            <td><?= $task->dealer !== null ?
                                $this->Html->link(
                                    $task->dealer->name,
                                    ['controller' => 'Customers', 'action' => 'view', $task->dealer->id],
                                ) : '' ?>
                            </td>
                            <td><?= h($task->subject) ?></td>
                            <td style="overflow-wrap: break-word; max-width: 600px;">
                                <?= nl2br($task->text ?? '') ?>
                            </td>
                            <td><?= h($task->email) ?></td>
                            <td><?= h($task->phone) ?></td>
                            <td><?= $task->customer !== null ?
                                $this->Html->link(
                                    $task->customer->name,
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
                                    __('View'),
                                    ['controller' => 'Tasks', 'action' => 'view', $task->id],
                                ) ?>
                                <?= $this->AuthLink->link(
                                    __('Edit'),
                                    ['controller' => 'Tasks', 'action' => 'edit', $task->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __('Delete'),
                                    ['controller' => 'Tasks', 'action' => 'delete', $task->id],
                                    ['confirm' => __('Are you sure you want to delete # {0}?', $task->number)],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="related">
                <h4><?= __('Related Contract States') ?></h4>

                <?php if (!empty($taskType->contract_states)) : ?>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th><?= __('Name') ?></th>
                                <th><?= __('Usable for New Contracts') ?></th>
                                <th><?= __('Active Services') ?></th>
                                <th><?= __('Billed') ?></th>
                                <th><?= __('Blocked') ?></th>
                                <th class="actions"><?= __('Actions') ?></th>
                            </tr>

                            <?php foreach ($taskType->contract_states as $contractState) : ?>
                            <tr>
                                <td><?= h($contractState->name) ?></td>
                                <td><?= $contractState->usable_for_new_contract ? __('Yes') : __('No') ?></td>
                                <td><?= $contractState->active_services ? __('Yes') : __('No') ?></td>
                                <td><?= $contractState->billed ? __('Yes') : __('No') ?></td>
                                <td><?= $contractState->blocked ? __('Yes') : __('No') ?></td>
                                <td class="actions">
                                    <?= $this->AuthLink->link(
                                        __('View'),
                                        ['controller' => 'ContractStates', 'action' => 'view', $contractState->id],
                                    ) ?>
                                    <?= $this->AuthLink->link(
                                        __('Edit'),
                                        ['controller' => 'ContractStates', 'action' => 'edit', $contractState->id],
                                        ['class' => 'win-link'],
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
