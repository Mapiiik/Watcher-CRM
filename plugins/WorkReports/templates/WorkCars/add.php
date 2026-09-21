<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkCar $record
 * @var list<array{value: string, text: string, style: string|null}> $owners
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'List Work Cars'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-cars form content">
            <?= $this->Form->create($record) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Add Work Car')) ?>
                <?php
                echo $this->Form->control('name', ['label' => __d('work_reports', 'Name')]);
                echo $this->Form->control('license_plate', ['label' => __d('work_reports', 'License Plate')]);
                echo $this->Form->control('owner_id', [
                    'label' => __d('work_reports', 'Owner'),
                    'options' => $owners,
                    'empty' => __d('work_reports', 'company car'),
                    'title' => __d('work_reports', 'Empty for a company car.'),
                ]);
                echo $this->Form->control('active', ['label' => __d('work_reports', 'Active')]);
                echo $this->Form->control('note', ['label' => __d('work_reports', 'Note')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
