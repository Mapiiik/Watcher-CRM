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
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __d('work_reports', 'Delete'),
                ['action' => 'delete', $record->id],
                [
                    'confirm' => __d('work_reports', 'Are you sure you want to delete # {0}?', $record->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
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
                <?= $this->legend(__d('work_reports', 'Edit Work Car')) ?>
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
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
