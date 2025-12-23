<?php
/**
 * @var \App\View\AppView $this
 */

use Bookkeeping\Model\Enum\InvoiceImportFormat;

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
                    'action' => 'importFromFile',
                ],
            ]) ?>
            <fieldset>
                <legend><?= __d('bookkeeping', 'Import Invoices from File') ?></legend>
                <div class="row">
                    <div class="column">
                    <?php
                        echo $this->Form->control('file', [
                            'label' => __d('bookkeeping', 'File for import'),
                            'type' => 'file',
                            'required' => true,
                        ]);
                        echo $this->Form->control('format', [
                            'empty' => true,
                            'type' => 'select',
                            'options' => InvoiceImportFormat::options(),
                            'label' => __d('bookkeeping', 'Import format'),
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
