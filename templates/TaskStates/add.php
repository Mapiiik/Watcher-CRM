<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TaskState $taskState
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Task States'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskStates form content">
            <?= $this->Form->create($taskState) ?>
            <fieldset>
                <legend><?= __('Add Task State') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('color', ['type' => 'color']);
                    echo $this->Form->control('priority');
                    echo $this->Form->control('completed');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
