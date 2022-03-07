<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping_pohoda', 'Actions') ?></h4>
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
                <legend><?= __d('bookkeeping_pohoda', 'Import Invoices from DBF') ?></legend>
                <div class="row">
                    <div class="column-responsive">
                    <?php
                        echo $this->Form->control('dbf_for_import', [
                            'label' => __d('bookkeeping_pohoda', 'DBF for import'),
                            'type' => 'file',
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column-responsive">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping_pohoda', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
