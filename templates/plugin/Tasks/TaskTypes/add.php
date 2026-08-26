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
                __d('app_tasks', 'List Task Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="taskTypes form content">
            <?= $this->Form->create($taskType) ?>
            <fieldset>
                <legend><?= __d('app_tasks', 'Add Task Type') ?></legend>
                <?php
                    echo $this->Form->control('name', ['label' => __d('app_tasks', 'Name')]);
                    echo $this->Form->control('customer_required', ['label' => __d('app_tasks', 'Customer Required')]);
                    echo $this->Form->control('contract_required', ['label' => __d('app_tasks', 'Contract Required')]);
                    echo $this->Form->control(
                        'access_point_required',
                        ['label' => __d('app_tasks', 'Access Point Required')],
                    );
                    echo $this->Form->control('report_on_completion', [
                        'label' => __d('app_tasks', 'Report on Completion'),
                        'title' => __d(
                            'app_tasks',
                            'Tell the report addresses whenever a task of this type is closed.',
                        ),
                    ]);
                    ?>
            </fieldset>
            <?= $this->Form->button(__d('app_tasks', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
