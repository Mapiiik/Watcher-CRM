<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReport $workReport
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
                <?= $this->legend(__d('work_reports', 'Note')) ?>
                <p>
                    <?= h($workReport->user->name) ?>, <?= h($workReport->month->i18nFormat('LLLL yyyy')) ?>
                </p>
                <br>
                <?= $this->Form->control('note', [
                    'label' => __d('work_reports', 'Note'),
                    'type' => 'textarea',
                    'style' => 'height: 8rem',
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
