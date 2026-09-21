<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\WorkReports\Model\Entity\WorkReport> $workReports
 * @var list<array{value: string, text: string, style: string|null}> $workers
 */
?>
<?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
<div class="row">
    <div class="column">
        <?= $this->Form->control('user_id', [
            'label' => __d('work_reports', 'User'),
            'options' => $workers,
            'empty' => true,
            'onchange' => $this::SUBMIT_ON_CHANGE,
        ]) ?>
    </div>
</div>
<?= $this->Form->end() ?>

<div class="work-reports index content">
    <?= $this->AuthLink->link(
        __d('work_reports', 'My Work Report'),
        ['action' => 'sheet'],
        ['class' => 'button float-right'],
    ) ?>
    <?= $this->heading(__d('work_reports', 'Work Reports')) ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('WorkReports.month', __d('work_reports', 'Month')) ?></th>
                    <th><?= $this->Paginator->sort('Users.last_name', __d('work_reports', 'User')) ?></th>
                    <th><?= __d('work_reports', 'Workload') ?></th>
                    <th><?= $this->Paginator->sort('WorkReports.submitted', __d('work_reports', 'Submitted')) ?></th>
                    <th class="actions"><?= __d('work_reports', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workReports as $workReport) : ?>
                <tr>
                    <td><?= h($workReport->month->i18nFormat('LLLL yyyy')) ?></td>
                    <td><?= h($workReport->user->name) ?></td>
                    <td><?= $this->Number->format($workReport->workload->toFloat()) ?></td>
                    <td><?= h($workReport->submitted) ?></td>
                    <td class="actions">
                        <?= $this->AuthLink->link(__d('work_reports', 'View'), [
                            'action' => 'sheet',
                            '?' => ['user_id' => $workReport->user_id, 'month' => $workReport->month->format('Y-m')],
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $this->element('common/paginator') ?>
</div>
