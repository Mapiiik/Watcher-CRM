<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkLabel $record
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $record->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $record->id), 'class' => 'side-nav-item'],
            ) ?>
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
                <?= $this->legend(__d('work_reports', 'Edit Work Label')) ?>
                <?php
                echo $this->Form->control('name', ['label' => __d('work_reports', 'Name')]);
                echo $this->Form->control('caption', ['label' => __d('work_reports', 'Caption')]);
                echo $this->Form->control('color', ['label' => __d('work_reports', 'Color'), 'type' => 'color']);
                echo $this->Form->control('active', ['label' => __d('work_reports', 'Active')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
