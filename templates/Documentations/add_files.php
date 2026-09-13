<?php
/**
 * Putting things into a folder.
 *
 * The form says how many the server takes and how much it takes altogether, because the server
 * does not: past either, PHP drops what is over without a word.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Documentation $documentation
 */
$this->Upload->load();
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __d('app_files', 'Actions') ?></h4>
            <?= $this->AuthLink->link(
                __d('app_files', 'Back to the Documentation'),
                ['action' => 'view', $documentation->id],
                ['class' => 'side-nav-item'],
            ) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="documentations form content">
            <?= $this->Form->create(null, ['type' => 'file'] + $this->Upload->atMost()) ?>
            <fieldset>
                <?= $this->legend(__d('app_files', 'Add Files to {0}', $documentation->heading)) ?>
                <?= $this->Form->control('files[]', [
                    'type' => 'file',
                    'multiple' => true,
                    'label' => __d('app_files', 'Files'),
                ]) ?>
            </fieldset>
            <?= $this->Form->button(__d('app_files', 'Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
