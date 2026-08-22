<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Task $task
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $taskTypes
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $taskStates
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $customers
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $users
 * @var \Cake\Collection\CollectionInterface<string, string>|array<string> $accessPoints
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Tasks'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="tasks form content">
            <?= $this->Form->create($task) ?>
            <fieldset>
                <legend><?= __('Add Task') ?></legend>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('task_type_id', [
                            'options' => $taskTypes,
                            'empty' => true,
                            'label' => __('Task Type'),
                        ]);
                        echo $this->Form->control('priority', [
                            'options' => $task->getPriorityOptions(),
                            'label' => __('Priority'),
                        ]);
                        echo $this->Form->control('task_state_id', [
                            'options' => $taskStates,
                            'label' => __('Task State'),
                        ]);
                        echo $this->Form->control('user_id', [
                            'options' => $users,
                            'empty' => true,
                            'label' => __('User'),
                        ]);
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('email', ['multiple' => 'multiple', 'label' => __('Email')]);
                        echo $this->Form->control('phone', ['multiple' => 'multiple', 'label' => __('Phone')]);
                        echo $this->Form->control('access_point_id', [
                            'options' => $accessPoints,
                            'empty' => true,
                            'label' => __('Access Point'),
                        ]);
                        echo $this->Form->control('customer_id', [
                            'options' => $customers,
                            'empty' => true,
                            'label' => __('Customer'),
                            'onchange' => $this::REFRESH_ON_CHANGE,
                        ]);
                        if (isset($task->customer_id)) {
                            echo $this->Form->control('contract_id', [
                                'options' => $contracts,
                                'empty' => true,
                                'label' => __('Contract'),
                                'onchange' => $this::REFRESH_ON_CHANGE,
                            ]);
                        }
                        $this->Form->unlockField('refresh'); //disable form security check
                        ?>
                    </div>
                </div>
                <?php
                echo $this->Form->control('subject', ['label' => __('Subject')]);
                echo $this->Form->control('text', ['style' => 'height: 30.0rem', 'label' => __('Text')]);
                ?>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('start_date', ['empty' => true, 'label' => __('Start Date')]);
                        echo $this->Form->control('estimated_date', ['empty' => true, 'label' => __('Estimated Date')]);
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('critical_date', ['empty' => true, 'label' => __('Critical Date')]);
                        echo $this->Form->control('finish_date', ['empty' => true, 'label' => __('Finish Date')]);
                        ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>

            <?php if ($task->customer_id !== null && $task->contract_id === null) : ?>
                <br>
                <div>
                    <iframe width="100%" height="500"  src="<?= $this->Url->build([
                        'controller' => 'Customers',
                        'action' => 'view',
                        $task->customer_id,
                        '?' => ['win-link' => 'true'],
                    ]) ?>"></iframe>
                </div>
            <?php endif ?>

            <?php if ($task->customer_id !== null && $task->contract_id !== null) : ?>
                <br>
                <div>
                    <iframe width="100%" height="500"  src="<?= $this->Url->build([
                        'controller' => 'Contracts',
                        'action' => 'view',
                        $task->contract_id,
                        'customer_id' => $task->customer_id,
                        '?' => ['win-link' => 'true'],
                    ]) ?>"></iframe>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
