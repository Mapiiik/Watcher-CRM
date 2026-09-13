<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Documentation $documentation
 * @var array<string, string> $kinds
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_files', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_files', 'List Documentations'),
                ['action' => 'index'],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documentations form content">
            <?= $this->Form->create($documentation) ?>
            <fieldset>
                <legend><?= __d('app_files', 'Edit Documentation') ?></legend>
                <?= $this->element('Documentations/fields', ['kinds' => $kinds]) ?>
            </fieldset>
            <?= $this->Form->button(__d('app_files', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
