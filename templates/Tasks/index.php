<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Task> $tasks
 * @var \Cake\Form\Form $filterForm
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $taskTypes
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $taskStates
 * @var bool $expandableText
 */

$this->Html->css('expandable-text', ['block' => true]);
$this->Html->script('expandable-text.js', ['block' => true]);
?>
<?= $this->Form->create($filterForm, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('user_id', [
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('task_type_ids', [
            'label' => __('Task Type'),
            'options' => $taskTypes,
            'multiple' => 'multiple',
            'style' => 'height: 100px;',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('task_state_ids', [
            'label' => __('Task State'),
            'options' => $taskStates,
            'multiple' => 'multiple',
            'style' => 'height: 100px;',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('access_point_id', [
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<div class="row">
    <div class="column">
        <?= $this->Form->control('show_completed', [
            'label' => __('Show Completed'),
            'type' => 'checkbox',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('pressing', [
            'label' => __('Urgent or Overdue Only'),
            'type' => 'checkbox',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
        <?= $this->Form->control('stale', [
            'label' => __('Untouched for a While Only'),
            'type' => 'checkbox',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
    <div class="column">
    </div>
    <div class="column">
        <?= $this->Form->control('expandable_text', [
            'label' => __('Expandable Text'),
            'type' => 'checkbox',
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="tasks index content">
    <?= $this->AuthLink->link(__('New Task'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <?= $this->AuthLink->link(__('Map'), ['action' => 'map'], ['class' => 'button float-right']) ?>
    <h3><?= __('Tasks') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('nid', __('Number')) ?></th>
                    <th><?= $this->Paginator->sort('task_type_id', __('Task Type')) ?></th>
                    <th><?= $this->Paginator->sort('priority', __('Priority')) ?></th>
                    <th><?= $this->Paginator->sort('TaskStates.priority', __('Task State')) ?></th>
                    <th><?= $this->Paginator->sort('user_id', __('User')) ?></th>
                    <th><?= $this->Paginator->sort('subject', __('Subject')) ?> / <i><?= __('Summary Text') ?></i></th>
                    <th><?= $this->Paginator->sort('text', __('Text')) ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer')) ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('contract_id', __('Contract')) ?></th>
                    <th><?= $this->Paginator->sort('access_point_id', __('Access Point')) ?></th>
                    <th><?= $this->Paginator->sort('start_date', __('Start Date')) ?></th>
                    <th><?= $this->Paginator->sort('estimated_date', __('Estimated Date')) ?></th>
                    <th><?= $this->Paginator->sort('critical_date', __('Critical Date')) ?></th>
                    <th><?= $this->Paginator->sort('finish_date', __('Finish Date')) ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task) : ?>
                <tr style="<?= $task->style ?>">
                    <td><?= h($task->number) ?></td>
                    <td>
                        <?= $task->task_type !== null ? $this->Html->link(
                            $task->task_type->name ?? '(' . $task->task_type->id . ')',
                            ['controller' => 'TaskTypes', 'action' => 'view', $task->task_type->id],
                        ) : '' ?>
                    </td>
                    <td><?= h($task->getPriorityName()) ?></td>
                    <td>
                        <?= $task->task_state !== null ? $this->Html->link(
                            $task->task_state->name ?? '(' . $task->task_state->id . ')',
                            ['controller' => 'TaskStates', 'action' => 'view', $task->task_state->id],
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $task->user !== null ? $this->Html->link(
                            $task->user->name ?? '(' . $task->user->id . ')',
                            ['controller' => 'AppUsers', 'action' => 'view', $task->user->id],
                        ) : '' ?>
                    </td>
                    <td>
                        <?= h($task->subject) ?? '&nbsp;' ?>
                        <blockquote><i><?= h($task->summary_text) ?></i></blockquote>
                    </td>
                    <td style="overflow-wrap: break-word; max-width: 600px;">
                        <?= $this->element('common/expandable_text', [
                            'text' => $task->text,
                            'lines' => 20,
                            'enabled' => $expandableText,
                            'mode' => 'end',
                        ]) ?>
                    </td>
                    <td>
                        <?= $task->customer !== null ? $this->Html->link(
                            $task->customer->name ?? '(' . $task->customer->id . ')',
                            ['controller' => 'Customers', 'action' => 'view', $task->customer->id],
                        ) : '' ?>
                    </td>
                    <td><?= $task->customer !== null ? h($task->customer->number) : '' ?></td>
                    <td><?=
                        $task->contract !== null ? $this->Html->link(
                            $task->contract->name ?? '(' . $task->contract->id . ')',
                            [
                                'controller' => 'Contracts',
                                'action' => 'view',
                                $task->contract->id,
                                'customer_id' => $task->contract->customer_id,
                            ],
                        ) : '' ?>
                    </td>
                    <td><?= $this->element('AccessPoints/link', [
                        'id' => $task->access_point_id,
                        'name' => $task->access_point->data?->name,
                        'answer' => $task->access_point,
                    ]) ?></td>
                    <td><?= h($task->start_date) ?></td>
                    <td><?= h($task->estimated_date) ?></td>
                    <td><?= h($task->critical_date) ?></td>
                    <td><?= h($task->finish_date) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(
                            __('View'),
                            ['action' => 'view', $task->id],
                        ) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $task->id],
                            ['class' => 'win-link'],
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
