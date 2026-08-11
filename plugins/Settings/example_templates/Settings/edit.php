<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var string $path
 * @var \Settings\ValueObject\SettingsPath $settingsPath
 * @var array<mixed> $default
 * @var array<mixed> $overlay
 * @var array<string, \Settings\ValueObject\SettingType> $types
 * @var array<string, string> $errors
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->AuthLink->link(__('List Settings'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column column-90">
        <div class="settings form content">
            <?= $this->Form->create() ?>
            <fieldset>
                <legend><?= __('Edit Setting') ?></legend>
                <?= __(
                    'If you leave the field blank, it will not be saved in the DB and the default value will be used.',
                ) ?>
            </fieldset>

            <fieldset class="settings-group">
                <legend><?= __('Setting: {path}', ['path' => $path]) ?></legend>

                <?= $this->element('Settings/FormFields', [
                    'default' => $default,
                    'overlay' => $overlay ?? [],
                    'types' => $types,
                    'errors' => $errors,
                    'path' => '',
                    'fullPath' => $path,
                ]) ?>
            </fieldset>

            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
