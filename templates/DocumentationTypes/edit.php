<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\DocumentationType $documentationType
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_files', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_files', 'List Documentation Types'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documentationTypes form content">
            <?= $this->Form->create($documentationType) ?>
            <fieldset>
                <legend><?= __d('app_files', 'Edit Documentation Type') ?></legend>
                <?= $this->element('DocumentationTypes/fields') ?>
            </fieldset>
            <?= $this->Form->button(__d('app_files', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
