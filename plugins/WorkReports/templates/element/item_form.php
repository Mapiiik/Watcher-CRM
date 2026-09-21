<?php
use WorkReports\Model\Enum\TimeMode;

/**
 * The form of a work report item, the same for adding and editing.
 *
 * Choosing the type, the whole day or the customer redraws the form, so that it offers only what
 * the choice leaves to fill in.
 *
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkReportItem $item
 * @var string $legend
 * @var list<array{value: string, text: string}> $types
 * @var \WorkReports\Model\Enum\TimeMode|null $timeMode
 * @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Customer> $customers
 * @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract>|array<string, string> $contracts
 * @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task>|array<string, string> $tasks
 * @var array<string, string> $accessPoints
 * @var \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar> $privateCars
 * @var \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkCar> $companyCars
 * @var \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkRate> $workRates
 * @var \Cake\ORM\Query\SelectQuery<\WorkReports\Model\Entity\WorkLabel> $workLabels
 * @var list<array{value: string, text: string, style: string|null}> $collaborators
 */

$showTimes = $timeMode !== TimeMode::WholeDay && !($timeMode === TimeMode::Either && $item->whole_day);
?>
<?= $this->Form->create($item) ?>
<fieldset>
    <?= $this->legend($legend) ?>
    <div class="row">
        <div class="column">
            <?php
            echo $this->Form->control('work_report_item_type_id', [
                'options' => $types,
                'empty' => true,
                'label' => __d('work_reports', 'Work Report Item Type'),
                'onchange' => $this::REFRESH_ON_CHANGE,
            ]);
            echo $this->Form->control('date', ['label' => __d('work_reports', 'Date')]);
            if ($timeMode === TimeMode::Either) {
                echo $this->Form->control('whole_day', [
                    'label' => __d('work_reports', 'Whole Day'),
                    'onchange' => $this::REFRESH_ON_CHANGE,
                ]);
            }
            ?>
            <?php if ($showTimes) : ?>
            <div class="row">
                <div class="column">
                    <?= $this->Form->control('time_from', [
                        'type' => 'time',
                        'label' => __d('work_reports', 'Work From'),
                    ]) ?>
                </div>
                <div class="column">
                    <?= $this->Form->control('time_until', [
                        'type' => 'time',
                        'label' => __d('work_reports', 'Work Until'),
                        'title' => __d('work_reports', 'Earlier than from means past midnight.'),
                    ]) ?>
                </div>
            </div>
            <?php endif ?>
        </div>
        <div class="column">
            <?php
            echo $this->Form->control('customer_id', [
                'options' => $customers,
                'empty' => true,
                'label' => __d('work_reports', 'Customer'),
                'onchange' => $this::REFRESH_ON_CHANGE,
            ]);
            if (isset($item->customer_id)) {
                echo $this->Form->control('contract_id', [
                    'options' => $contracts,
                    'empty' => true,
                    'label' => __d('work_reports', 'Contract'),
                ]);
                echo $this->Form->control('task_id', [
                    'options' => $tasks,
                    'empty' => true,
                    'label' => __d('work_reports', 'Task'),
                ]);
            }
            echo $this->Form->control('access_point_id', [
                'options' => $accessPoints,
                'empty' => true,
                'label' => __d('work_reports', 'Access Point'),
            ]);
            $this->Form->unlockField('refresh'); //disable form security check
            ?>
        </div>
    </div>
    <?= $this->Form->control('description', [
        'label' => __d('work_reports', 'Description'),
        'style' => 'height: 8rem',
    ]) ?>
    <div class="row">
        <div class="column">
            <div class="row">
                <div class="column">
                    <?= $this->Form->control('private_car_id', [
                        'options' => $privateCars,
                        'empty' => true,
                        'label' => __d('work_reports', 'Private Car'),
                    ]) ?>
                </div>
                <div class="column">
                    <?= $this->Form->control('private_car_distance', [
                        'label' => __d('work_reports', 'Private Car Distance'),
                        'title' => __d('work_reports', 'Kilometres'),
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="column">
                    <?= $this->Form->control('company_car_id', [
                        'options' => $companyCars,
                        'empty' => true,
                        'label' => __d('work_reports', 'Company Car'),
                    ]) ?>
                </div>
                <div class="column">
                    <?= $this->Form->control('company_car_distance', [
                        'label' => __d('work_reports', 'Company Car Distance'),
                        'title' => __d('work_reports', 'Kilometres'),
                    ]) ?>
                </div>
            </div>
            <?= $this->Form->control('cash_collected', ['label' => __d('work_reports', 'Cash Collected')]) ?>
        </div>
        <div class="column">
            <?php
            echo $this->Form->control('work_labels._ids', [
                'options' => $workLabels,
                'multiple' => 'multiple',
                'style' => 'height: 100px;',
                'label' => __d('work_reports', 'Work Labels'),
            ]);
            echo $this->Form->control('collaborators._ids', [
                'options' => $collaborators,
                'multiple' => 'multiple',
                'style' => 'height: 100px;',
                'label' => __d('work_reports', 'Collaborators'),
                'title' => __d('work_reports', 'Who else was there.'),
            ]);
            ?>
        </div>
    </div>
</fieldset>
<fieldset>
    <legend><?= __d('work_reports', 'Invoicing') ?></legend>
    <?= $this->Form->control('to_invoice', [
        'label' => __d('work_reports', 'To Invoice'),
        'onchange' => $this::REFRESH_ON_CHANGE,
    ]) ?>
    <?php if ($item->to_invoice) : ?>
        <div class="row">
            <div class="column">
                <?= $this->Form->control('invoice_hours', ['label' => __d('work_reports', 'Invoice Hours')]) ?>
            </div>
            <div class="column">
                <?= $this->Form->control('work_rate_id', [
                    'options' => $workRates,
                    'empty' => true,
                    'label' => __d('work_reports', 'Work Rate'),
                ]) ?>
            </div>
            <div class="column">
                <?= $this->Form->control('rate_multiplier', [
                    'label' => __d('work_reports', 'Rate Multiplier'),
                    'title' => __d('work_reports', 'For example 3 for a Sunday.'),
                ]) ?>
            </div>
        </div>
        <?= $this->Form->control('invoice_text', [
            'label' => __d('work_reports', 'Invoice Text'),
            'title' => __d('work_reports', 'What goes on the invoice.'),
            'style' => 'height: 5rem',
        ]) ?>
        <?= $this->Form->control('invoiced', ['label' => __d('work_reports', 'Invoiced')]) ?>
    <?php endif ?>
</fieldset>
<?= $this->Form->control('note', ['label' => __d('work_reports', 'Note'), 'style' => 'height: 5rem']) ?>
<?= $this->Form->button(__('Submit')) ?>
<?= $this->Form->end() ?>
