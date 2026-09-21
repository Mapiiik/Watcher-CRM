<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReportWorker $record
 * @var list<array{value: string, text: string, style: string|null}> $people
 * @var \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar> $privateCars
 * @var \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar> $companyCars
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
                __d('work_reports', 'List Work Report Workers'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-report-workers form content">
            <?= $this->Form->create($record) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Edit Work Report Worker')) ?>
                <?php
                echo $this->Form->control('user_id', ['label' => __d('work_reports', 'User'), 'options' => $people]);
                echo $this->Form->control('workload', [
                    'label' => __d('work_reports', 'Workload'),
                    'title' => __d('work_reports', '1 is full time, 0.5 half time.'),
                ]);
                echo $this->Form->control('supervisor_id', [
                    'label' => __d('work_reports', 'Supervisor'),
                    'options' => $people,
                    'empty' => true,
                    'title' => __d('work_reports', 'Receives the submitted report.'),
                ]);
                echo $this->Form->control('default_private_car_id', [
                    'label' => __d('work_reports', 'Default Private Car'),
                    'options' => $privateCars,
                    'empty' => true,
                ]);
                echo $this->Form->control('default_company_car_id', [
                    'label' => __d('work_reports', 'Default Company Car'),
                    'options' => $companyCars,
                    'empty' => true,
                ]);
                echo $this->Form->control('active', ['label' => __d('work_reports', 'Active')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
