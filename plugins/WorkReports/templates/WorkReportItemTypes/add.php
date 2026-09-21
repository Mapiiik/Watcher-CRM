<?php
use WorkReports\Model\Enum\TimeMode;

/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReportItemType $record
 */

$timeModes = [];
foreach (TimeMode::cases() as $case) {
    $timeModes[$case->value] = $case->label();
}
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'List Work Report Item Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-report-item-types form content">
            <?= $this->Form->create($record) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Add Work Report Item Type')) ?>
                <?php
                echo $this->Form->control('name', ['label' => __d('work_reports', 'Name')]);
                echo $this->Form->control('time_mode', [
                    'label' => __d('work_reports', 'Time Mode'),
                    'options' => $timeModes,
                ]);
                echo $this->Form->control('description_required', [
                    'label' => __d('work_reports', 'Description Required'),
                ]);
                echo $this->Form->control('counts_as_worked', [
                    'label' => __d('work_reports', 'Counts As Worked'),
                    'title' => __d('work_reports', 'The time counts as worked.'),
                ]);
                echo $this->Form->control('reduces_fund', [
                    'label' => __d('work_reports', 'Reduces Fund'),
                    'title' => __d('work_reports', 'A whole day lowers the fund.'),
                ]);
                echo $this->Form->control('active', ['label' => __d('work_reports', 'Active')]);
                echo $this->Form->control('position', ['label' => __d('work_reports', 'Position')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
