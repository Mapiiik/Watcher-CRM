<?php
/**
 * @var \App\View\AppView $this
 * @var \WorkReports\Model\Entity\WorkRate $record
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('work_reports', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('work_reports', 'List Work Rates'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="work-rates form content">
            <?= $this->Form->create($record) ?>
            <fieldset>
                <?= $this->legend(__d('work_reports', 'Add Work Rate')) ?>
                <?php
                echo $this->Form->control('code', ['label' => __d('work_reports', 'Code')]);
                echo $this->Form->control('name', ['label' => __d('work_reports', 'Name')]);
                echo $this->Form->control('price', ['label' => __d('work_reports', 'Price Per Hour')]);
                echo $this->Form->control('accounting_product_code', [
                    'label' => __d('work_reports', 'Accounting Product Code'),
                ]);
                echo $this->Form->control('active', ['label' => __d('work_reports', 'Active')]);
                ?>
            </fieldset>
            <?= $this->Form->button(__d('work_reports', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
