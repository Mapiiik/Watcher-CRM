<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskType $taskType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_tasks', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_tasks', 'Edit Task Type'),
                ['action' => 'edit', $taskType->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('app_tasks', 'Delete Task Type'),
                ['action' => 'delete', $taskType->id],
                [
                    'confirm' => __d('app_tasks', 'Are you sure you want to delete # {0}?', $taskType->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __d('app_tasks', 'List Task Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('app_tasks', 'New Task Type'),
                ['action' => 'add'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskTypes view content">
            <h3><?= h($taskType->name) ?></h3>
            <div class="row">
                <div class="column">
                    <table>
                        <tr>
                            <th><?= __d('app_tasks', 'Name') ?></th>
                            <td><?= h($taskType->name) ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_tasks', 'Customer Required') ?></th>
                            <td><?= $taskType->customer_required
                                ? __d('app_tasks', 'Yes')
                                : __d('app_tasks', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_tasks', 'Contract Required') ?></th>
                            <td><?= $taskType->contract_required
                                ? __d('app_tasks', 'Yes')
                                : __d('app_tasks', 'No'); ?></td>
                        </tr>
                        <tr>
                            <th><?= __d('app_tasks', 'Access Point Required') ?></th>
                            <td><?= $taskType->access_point_required
                                ? __d('app_tasks', 'Yes')
                                : __d('app_tasks', 'No'); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="column">
                    <?= $this->element('common/audit', ['entity' => $taskType]) ?>
                </div>
            </div>
            <div class="related">
                <h4><?= __d('app_tasks', 'Related Tasks') ?></h4>
                <?= $this->element('Tasks/related', [
                    'tasks' => $taskType->tasks,
                    'task_state_column' => true,
                ]) ?>
            </div>
            <div class="related">
                <h4><?= __d('app_tasks', 'Related Contract States') ?></h4>

                <?php if (!empty($taskType->contract_states)) : ?>
                    <div class="table-responsive">
                        <table>
                            <tr>
                                <th><?= __d('app_tasks', 'Name') ?></th>
                                <th><?= __d('app_tasks', 'Usable for New Contracts') ?></th>
                                <th><?= __d('app_tasks', 'Active Services') ?></th>
                                <th><?= __d('app_tasks', 'Billed') ?></th>
                                <th><?= __d('app_tasks', 'Blocked') ?></th>
                                <th class="actions"><?= __d('app_tasks', 'Actions') ?></th>
                            </tr>

                            <?php foreach ($taskType->contract_states as $contractState) : ?>
                            <tr>
                                <td><?= h($contractState->name) ?></td>
                                <td><?= $contractState->usable_for_new_contract
                                    ? __d('app_tasks', 'Yes')
                                    : __d('app_tasks', 'No') ?></td>
                                <td><?= $contractState->active_services
                                    ? __d('app_tasks', 'Yes')
                                    : __d('app_tasks', 'No') ?></td>
                                <td><?= $contractState->billed
                                    ? __d('app_tasks', 'Yes')
                                    : __d('app_tasks', 'No') ?></td>
                                <td><?= $contractState->blocked
                                    ? __d('app_tasks', 'Yes')
                                    : __d('app_tasks', 'No') ?></td>
                                <td class="actions">
                                    <?= $this->AuthLink->link(
                                        __d('app_tasks', 'View'),
                                        ['controller' => 'ContractStates', 'action' => 'view', $contractState->id],
                                    ) ?>
                                    <?= $this->AuthLink->link(
                                        __d('app_tasks', 'Edit'),
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
