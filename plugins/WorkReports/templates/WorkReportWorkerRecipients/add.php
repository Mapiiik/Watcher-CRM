<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReportWorkerRecipient $recipient
 * @var list<array{value: string, text: string, style: string|null}> $people
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'Work Report Worker'),
                ['controller' => 'WorkReportWorkers', 'action' => 'view', $recipient->work_report_worker_id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-report-worker-recipients form content">
            <?= $this->Form->create($recipient) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Add Recipient')) ?>
                <?php
                echo $this->Form->hidden('work_report_worker_id');
                echo $this->Form->control('user_id', ['label' => __d('work_reports', 'User'), 'options' => $people]);
                echo $this->Form->control('may_edit', [
                    'label' => __d('work_reports', 'May Edit'),
                    'title' => __d('work_reports', 'May change the items and return the report.'),
                ]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
