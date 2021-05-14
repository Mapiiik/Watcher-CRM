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
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $task->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $task->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Tasks'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="tasks form content">
            <?= $this->Form->create($task) ?>
            <fieldset>
                <legend><?= __('Edit Task') ?></legend>
                <?php
                    echo $this->Form->control('task_type_id', ['options' => $taskTypes]);
                    echo $this->Form->control('priority');
                    echo $this->Form->control('task_state_id', ['options' => $taskStates]);
                    echo $this->Form->control('subject');
                    echo $this->Form->control('text', ['style' => 'height: 30.0rem']);
                    echo $this->Form->control('email');
                    echo $this->Form->control('phone');
                    echo $this->Form->control('customer_id', ['options' => $customers, 'empty' => true]);
                    echo $this->Form->control('dealer_id', ['options' => $dealers, 'empty' => true]);
                    echo $this->Form->control('router_id', ['options' => $routers, 'empty' => true]);
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
