<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Task[]|\Cake\Collection\CollectionInterface $tasks
 * @var string[]|\Cake\Collection\CollectionInterface $priorities
 * @var \Cake\Form\Form $filterForm
 */
?>
<?= $this->Form->create($filterForm, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column-responsive">
        <?= $this->Form->control('dealer_id', [
            'empty' => true,
            'onchange' => 'this.form.submit();',
        ]) ?>
        <?= $this->Form->control('show_completed', [
            'label' => __('Show Completed'),
            'type' => 'checkbox',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column-responsive">
        <?= $this->Form->control('task_type_id', [
            'empty' => true,
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column-responsive">
        <?= $this->Form->control('task_state_id', [
            'empty' => true,
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column-responsive">
        <?= $this->Form->control('access_point_id', [
            'empty' => true,
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
    <div class="column-responsive">
        <?= $this->Form->control('search', [
            'label' => __('Search'),
            'type' => 'search',
            'onchange' => 'this.form.submit();',
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="tasks index content">
    <?= $this->AuthLink->link(__('New Task'), ['action' => 'add'], ['class' => 'button float-right win-link']) ?>
    <h3><?= __('Tasks') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('task_type_id') ?></th>
                    <th><?= $this->Paginator->sort('priority') ?></th>
                    <th><?= $this->Paginator->sort('task_state_id') ?></th>
                    <th><?= $this->Paginator->sort('dealer_id') ?></th>
                    <th><?= $this->Paginator->sort('subject') ?></th>
                    <th><?= $this->Paginator->sort('text') ?></th>
                    <th><?= $this->Paginator->sort('customer_id') ?></th>
                    <th><?= $this->Paginator->sort('customer_id', __('Customer Number')) ?></th>
                    <th><?= $this->Paginator->sort('access_point_id') ?></th>
                    <th><?= $this->Paginator->sort('start_date') ?></th>
                    <th><?= $this->Paginator->sort('estimated_date') ?></th>
                    <th><?= $this->Paginator->sort('critical_date') ?></th>
                    <th><?= $this->Paginator->sort('finish_date') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task) : ?>
                <tr style="<?= $task->style ?>">
                    <td>
                        <?= $task->has('task_type') ? $this->Html->link(
                            $task->task_type->name,
                            ['controller' => 'TaskTypes', 'action' => 'view', $task->task_type->id]
                        ) : '' ?>
                    </td>
                    <td><?= $priorities[$task->priority] ?></td>
                    <td>
                        <?= $task->has('task_state') ? $this->Html->link(
                            $task->task_state->name,
                            ['controller' => 'TaskStates', 'action' => 'view', $task->task_state->id]
                        ) : '' ?>
                    </td>
                    <td>
                        <?= $task->has('dealer') ? $this->Html->link(
                            $task->dealer->name,
                            ['controller' => 'Customers', 'action' => 'view', $task->dealer->id]
                        ) : '' ?>
                    </td>
                    <td><?= h($task->subject) ?></td>
                    <td><?= nl2br($task->text) ?></td>
                    <td>
                        <?= $task->has('customer') ? $this->Html->link(
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
                        <?= $this->AuthLink->link(__('View'), ['action' => 'view', $task->id]) ?>
                        <?= $this->AuthLink->link(
                            __('Edit'),
                            ['action' => 'edit', $task->id],
                            ['class' => 'win-link']
                        ) ?>
                        <?= $this->AuthLink->postLink(
                            __('Delete'),
                            ['action' => 'delete', $task->id],
                            ['confirm' => __('Are you sure you want to delete # {0}?', $task->id)]
                        ) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(
            __('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')
        ) ?></p>
    </div>
</div>
