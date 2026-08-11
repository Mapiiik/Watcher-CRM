<?php
declare(strict_types=1);

/**
 * Recursive form field generator for settings.
 *
 * A setting declared with a type is drawn as the type asks and is a leaf, whatever its value looks
 * like. Everything else is drawn as before: a group is walked into, a scalar gets a field, and the
 * name it carries decides whether that field is a line or a box.
 *
 * @var \App\View\AppView $this
 * @var array  $default  Default values (nested array)
 * @var array  $overlay  Overlay values from DB (nested array)
 * @var array<string, \Settings\ValueObject\SettingType> $types Declared types, by full path
 * @var array<string, string> $errors Refused values, by full path
 * @var string $path     Current dot-path (e.g. "invoices.phone")
 * @var string $fullPath Current full-path (e.g. "core.company.invoices.phone")
 */

use Settings\ValueObject\SettingWidget;

?>
<?php
foreach ($default as $key => $defaultValue) {
    $actualFullPath = ltrim($fullPath . '.' . $key, '.');
    $actualPath = ltrim($path . '.' . $key, '.');
    $actualOverlay = $overlay[$key] ?? null;
    $type = $types[$actualFullPath] ?? null;
    $error = $errors[$actualFullPath] ?? null;

    if ($type !== null) {
        // Declared setting
        $widget = $type->widget();

        // the blank choice is what every other field says by being left empty: use what was shipped
        $answers = ['' => __('Default'), '1' => __('Yes'), '0' => __('No')];

        if ($widget === SettingWidget::TriState) {
            $options = [
                'type' => 'select',
                'options' => $answers,
                'value' => $type->toFormValue($actualOverlay),
            ];
        } else {
            $options = [
                'type' => $widget === SettingWidget::Json ? 'textarea' : $widget->value,
                'value' => $actualOverlay !== null ? $type->toFormValue($actualOverlay) : '',
                'placeholder' => $type->toFormValue($defaultValue),
            ];
        }

        // what the type asks for on top, without letting it undo what the form settled above
        $options = array_merge($type->formOptions(), $options);

        $shownDefault = $type->toFormValue($defaultValue);
        if ($widget === SettingWidget::TriState) {
            $shownDefault = $answers[$shownDefault] ?? $shownDefault;
        }
        ?>
        <div class="input settings-field<?= $error !== null ? ' error' : '' ?>">
            <?= $this->Form->control("overlay.$actualPath", $options) ?>

            <?php if ($error !== null) : ?>
                <div class="error-message"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($type->hint() !== null) : ?>
                <small class="hint"><?= h($type->hint()) ?></small>
                <br>
            <?php endif; ?>

            <small class="default-value">
                <?= __('Default value:') ?>
                <pre><?= h($shownDefault) ?></pre>
                <br>
            </small>
        </div>

        <?php
    } elseif (is_array($defaultValue)) {
        // Nested group
        ?>
        <fieldset class="settings-group">
            <legend><?= __('Setting: {path}', ['path' => $actualFullPath]) ?></legend>

            <?= $this->element('Settings/FormFields', [
                'default' => $defaultValue,
                'overlay' => is_array($actualOverlay) ? $actualOverlay : [],
                'types' => $types,
                'errors' => $errors,
                'path' => $actualPath,
                'fullPath' => $actualFullPath,
            ]) ?>
        </fieldset>

        <?php
    } else {
        // Scalar value
        $controlType = 'text';
        if (str_ends_with($key, '_text') || str_ends_with($key, '_html')) {
            $controlType = 'textarea';
        }
        ?>
        <div class="input text settings-field">
            <?= $this->Form->control("overlay.$actualPath", [
                'type' => $controlType,
                'value' => $actualOverlay ?? '',
                'placeholder' => $defaultValue,
            ]) ?>

            <small class="default-value">
                <?= __('Default value:') ?>
                <pre><?= h($defaultValue) ?></pre>
                <br>
            </small>
        </div>
        <?php
    }
}
