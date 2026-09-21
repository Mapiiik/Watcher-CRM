<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReportWorker $record
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'Edit Work Report Worker'),
                ['action' => 'edit', $record->id],
                ['class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->postLink(
                __d('work_reports', 'Delete Work Report Worker'),
                ['action' => 'delete', $record->id],
                [
                    'confirm' => __d('work_reports', 'Are you sure you want to delete # {0}?', $record->id),
                    'class' => 'side-nav-item',
                ],
            ) ?>
            <?= $this->AuthLink->link(
                __d('work_reports', 'List Work Report Workers'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-report-workers view content">
            <?= $this->record(__d('work_reports', 'Work Report Worker'), $record->user->name) ?>
            <table>
                <tr>
                    <th><?= __d('work_reports', 'Workload') ?></th>
                    <td><?= $this->Number->format($record->workload->toFloat()) ?></td>
                </tr>
                <tr>
                    <th><?= __d('work_reports', 'Default Private Car') ?></th>
                    <td><?= h($record->default_private_car?->name_for_lists) ?></td>
                </tr>
                <tr>
                    <th><?= __d('work_reports', 'Default Company Car') ?></th>
                    <td><?= h($record->default_company_car?->name_for_lists) ?></td>
                </tr>
                <tr>
                    <th><?= __d('work_reports', 'Active') ?></th>
                    <td><?= $record->active ? __d('work_reports', 'Yes') : __d('work_reports', 'No') ?></td>
                </tr>
            </table>
            <div class="related">
                <?= $this->AuthLink->link(
                    __d('work_reports', 'New Recipient'),
                    [
                        'controller' => 'WorkReportWorkerRecipients',
                        'action' => 'add',
                        '?' => ['work_report_worker_id' => $record->id],
                    ],
                    ['class' => 'button button-small float-right win-link'],
                ) ?>
                <h4><?= __d('work_reports', 'Recipients') ?></h4>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __d('work_reports', 'User') ?></th>
                            <th><?= __d('work_reports', 'May Edit') ?></th>
                            <th class="actions"><?= __d('work_reports', 'Actions') ?></th>
                        </tr>
                        <?php foreach ($record->work_report_worker_recipients as $recipient) : ?>
                        <tr>
                            <td><?= h($recipient->user->name) ?></td>
                            <td>
                                <?= $recipient->may_edit ? __d('work_reports', 'Yes') : __d('work_reports', 'No') ?>
                            </td>
                            <td class="actions">
                                <?= $this->AuthLink->link(
                                    __d('work_reports', 'Edit'),
                                    ['controller' => 'WorkReportWorkerRecipients', 'action' => 'edit', $recipient->id],
                                    ['class' => 'win-link'],
                                ) ?>
                                <?= $this->AuthLink->postLink(
                                    __d('work_reports', 'Delete'),
                                    [
                                        'controller' => 'WorkReportWorkerRecipients',
                                        'action' => 'delete',
                                        $recipient->id,
                                    ],
                                    [
                                        'confirm' => __d(
                                            'work_reports',
                                            'Are you sure you want to delete # {0}?',
                                            $recipient->id,
                                        ),
                                    ],
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
