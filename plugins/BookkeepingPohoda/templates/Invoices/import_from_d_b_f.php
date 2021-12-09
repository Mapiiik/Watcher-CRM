<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
        </div>
    </aside>
    <div class="column-responsive column-90">
        <div class="invoices form content">
            <?= $this->Form->create(null, [
                'type' => 'file',
                'valueSources' => ['data', 'query'],
                'url' => [
                    'action' => 'importFromDBF',
                ],
            ]) ?>
            <fieldset>
                <legend><?= __('Import Invoices from DBF') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('dbf_for_import', [
                            'label' => __('DBF for import'),
                            'type' => 'file',
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column-responsive">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
