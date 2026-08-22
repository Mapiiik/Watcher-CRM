<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskState $taskState
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('tasks', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('tasks', 'List Task States'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskStates form content">
            <?= $this->Form->create($taskState) ?>
            <fieldset>
                <legend><?= __d('tasks', 'Add Task State') ?></legend>
                <?php
                    echo $this->Form->control('name', ['label' => __d('tasks', 'Name')]);
                    echo $this->Form->control('color', ['type' => 'color', 'label' => __d('tasks', 'Color')]);
                    echo $this->Form->control('priority', ['label' => __d('tasks', 'Priority')]);
                    echo $this->Form->control('completed', ['label' => __d('tasks', 'Completed')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('tasks', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
