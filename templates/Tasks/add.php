<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Task $task
 * @var string[]|\Cake\Collection\CollectionInterface $taskTypes
 * @var string[]|\Cake\Collection\CollectionInterface $taskStates
 * @var string[]|\Cake\Collection\CollectionInterface $priorities
 * @var string[]|\Cake\Collection\CollectionInterface $customers
 * @var string[]|\Cake\Collection\CollectionInterface $dealers
 * @var string[]|\Cake\Collection\CollectionInterface $accessPoints
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Tasks'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="tasks form content">
            <?= $this->Form->create($task) ?>
            <fieldset>
                <legend><?= __('Add Task') ?></legend>
                <?php
                    echo $this->Form->control('task_type_id', ['options' => $taskTypes]);
                    echo $this->Form->control('priority');
                    echo $this->Form->control('task_state_id', ['options' => $taskStates]);
                    echo $this->Form->control('subject');
                    echo $this->Form->control('text', ['style' => 'height: 30.0rem']);
                    echo $this->Form->control('email', ['multiple' => 'multiple']);
                    echo $this->Form->control('phone', ['multiple' => 'multiple']);
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                    echo $this->Form->control('dealer_id', ['options' => $dealers, 'empty' => true]);
                    echo $this->Form->control('access_point_id', ['options' => $accessPoints, 'empty' => true]);
                    echo $this->Form->control('start_date', ['empty' => true]);
                    echo $this->Form->control('estimated_date', ['empty' => true]);
                    echo $this->Form->control('critical_date', ['empty' => true]);
                    echo $this->Form->control('finish_date', ['empty' => true]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
