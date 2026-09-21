<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkLabel $record
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'List Work Labels'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-labels form content">
            <?= $this->Form->create($record) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Add Work Label')) ?>
                <?php
                echo $this->Form->control('name', ['label' => __d('work_reports', 'Name')]);
                echo $this->Form->control('caption', ['label' => __d('work_reports', 'Caption')]);
                echo $this->Form->control('color', ['label' => __d('work_reports', 'Color'), 'type' => 'color']);
                echo $this->Form->control('active', ['label' => __d('work_reports', 'Active')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
