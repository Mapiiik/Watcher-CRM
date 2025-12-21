<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('bookkeeping', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('bookkeeping', 'List Invoices'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="invoices form content">
            <?= $this->Form->create(null, [
                'type' => 'file',
                'valueSources' => ['data', 'query'],
                'url' => [
                    'action' => 'importFromDBF',
                ],
            ]) ?>
            <fieldset>
                <legend><?= __d('bookkeeping', 'Import Invoices from DBF') ?></legend>
                <div class="row">
                    <div class="column">
                    <?php
                        echo $this->Form->control('dbf_for_import', [
                            'label' => __d('bookkeeping', 'DBF for import'),
                            'type' => 'file',
                            'required' => true,
                        ]);
                        ?>
                    </div>
                    <div class="column">
                    </div>
                </div>
            </fieldset>
            <?= $this->Form->button(__d('bookkeeping', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
