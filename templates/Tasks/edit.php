<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Task $task
 * @var \Cake\Collection\CollectionInterface|array<string> $taskTypes
 * @var \Cake\Collection\CollectionInterface|array<string> $taskStates
 * @var \Cake\Collection\CollectionInterface|array<string> $customers
 * @var \Cake\Collection\CollectionInterface|array<string> $contracts
 * @var \Cake\Collection\CollectionInterface|array<string> $dealers
 * @var \Cake\Collection\CollectionInterface|array<string> $accessPoints
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $task->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $task->nid), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->AuthLink->link(__('List Tasks'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="tasks form content">
            <?= $this->Form->create($task) ?>
            <fieldset>
                <legend><?= __('Edit Task') ?></legend>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('task_type_id', ['options' => $taskTypes]);
                        echo $this->Form->control('priority', ['options' => $task->getPriorityOptions()]);
                        echo $this->Form->control('task_state_id', ['options' => $taskStates]);
                        echo $this->Form->control('dealer_id', ['options' => $dealers, 'empty' => true]);
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('email', ['multiple' => 'multiple']);
                        echo $this->Form->control('phone', ['multiple' => 'multiple']);
                        echo $this->Form->control('access_point_id', ['options' => $accessPoints, 'empty' => true]);
                        echo $this->Form->control('customer_id', [
                            'options' => $customers,
                            'empty' => true,
                            'onchange' => '
                                var refresh = document.createElement("input");
                                refresh.type = "hidden";
                                refresh.name = "refresh";
                                refresh.value = "refresh";
                                this.form.appendChild(refresh);
                                this.form.submit();
                            ',
                        ]);
                        if (isset($task->customer_id)) {
                            echo $this->Form->control('contract_id', [
                                'options' => $contracts,
                                'empty' => true,
                                'onchange' => '
                                    var refresh = document.createElement("input");
                                    refresh.type = "hidden";
                                    refresh.name = "refresh";
                                    refresh.value = "refresh";
                                    this.form.appendChild(refresh);
                                    this.form.submit();
                                ',
                            ]);
                        }
                        $this->Form->unlockField('refresh'); //disable form security check
                        ?>
                    </div>
                </div>
                <?php
                echo $this->Form->control('subject');
                echo $this->Form->control('text', ['style' => 'height: 30.0rem']);
                ?>
                <div class="row">
                    <div class="column">
                        <?php
                        echo $this->Form->control('start_date', ['empty' => true]);
                        echo $this->Form->control('estimated_date', ['empty' => true]);
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo $this->Form->control('critical_date', ['empty' => true]);
                        echo $this->Form->control('finish_date', ['empty' => true]);
                        ?>
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>

            <?php if ($task->__isset('customer_id') && !$task->__isset('contract_id')) : ?>
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

            <?php if ($task->__isset('customer_id') && $task->__isset('contract_id')) : ?>
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
