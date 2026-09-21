<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReportItem $item
 * @var string $userId
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $item->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $item->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(
                __d('work_reports', 'Work Report'),
                [
                    'controller' => 'WorkReports',
                    'action' => 'sheet',
                    '?' => ['user_id' => $userId, 'month' => $item->date?->format('Y-m')],
                ],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-report-items form content">
            <?= $this->element('WorkReports.item_form', ['legend' => __d('work_reports', 'Edit Work Report Item')]) ?>
        </div>
    </div>
</div>
