<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReport $workReport
 * @var bool $mayOpen
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'Work Report'),
                [
                    'action' => 'sheet',
                    '?' => ['user_id' => $workReport->user_id, 'month' => $workReport->month->format('Y-m')],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-reports form content">
            <?= $this->Form->create($workReport) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Close Days')) ?>
                <p>
                    <?= h($workReport->user->name) ?>, <?= h($workReport->month->i18nFormat('LLLL yyyy')) ?>
                </p>
                <?= $this->Form->control('closed_until', [
                    'label' => __d('work_reports', 'Closed Until'),
                    'type' => 'date',
                    'min' => $workReport->month->firstOfMonth()->format('Y-m-d'),
                    'max' => $workReport->month->lastOfMonth()->format('Y-m-d'),
                    'help' => __d(
                        'work_reports',
                        'The day and everything before it stays as it is written. The days after it'
                        . ' go on being filled in.',
                    ) . ($mayOpen
                        ? ''
                        : ' ' . __d('work_reports', 'Only a supervisor opens days that are closed.')),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
