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
            <?= $this->AuthLink->link(__('List Task Types'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskTypes form content">
            <?= $this->Form->create($taskType) ?>
            <fieldset>
                <legend><?= __('Add Task Type') ?></legend>
                <?php
                    echo $this->Form->control('name');
                    echo $this->Form->control('customer_required');
                    echo $this->Form->control('contract_required');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
