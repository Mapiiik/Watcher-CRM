<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Setting $setting
 * @var \Cake\Collection\CollectionInterface<array-key, string>|array<string> $creators
 * @var \Cake\Collection\CollectionInterface<array-key, string>|array<string> $modifiers
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->postLink(
                __('Delete'),
                ['action' => 'delete', $setting->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $setting->id), 'class' => 'side-nav-item'],
            ) ?>
            <?= $this->AuthLink->link(__('List Settings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="settings form content">
            <?= $this->Form->create($setting) ?>
            <fieldset>
                <legend><?= __('Edit Setting') ?></legend>
                <?= __(
                    'If you leave the field blank, it will not be saved in the DB and the default value will be used.',
                ) ?>
            </fieldset>

            <fieldset class="settings-group">
                <legend><?= __('Setting: {path}', ['path' => $plugin . '.' . $key]) ?></legend>

                <?= $this->element('Settings/FormFields', [
                    'data' => $default,
                    'overlay' => $setting->value ?? [],
                    'path' => '',
                    'fullPath' => $plugin . '.' . $key,
                ]) ?>
            </fieldset>

            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
