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
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
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
                <?= $this->legend(__d('work_reports', 'Add Work Report Worker')) ?>
                <?php
                echo $this->Form->control('user_id', ['label' => __d('work_reports', 'User'), 'options' => $people]);
                echo $this->Form->control('workload', [
                    'label' => __d('work_reports', 'Workload'),
                    'title' => __d('work_reports', '1 is full time, 0.5 half time.'),
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
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
