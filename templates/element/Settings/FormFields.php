<?php
/**
 * Recursive form field generator for settings.
 *
 * @var array  $data     Default values (nested array)
 * @var array  $overlay  Overlay values from DB (nested array)
 * @var string $path     Current dot-path (e.g. "invoices.phone")
 * @var string $fullPath Current full-path (e.g. "core.company.invoices.phone")
 */

foreach ($data as $key => $value) {
    $actualFullPath = ltrim($fullPath . '.' . $key, '.');
    $actualPath = ltrim($path . '.' . $key, '.');
    $overlayValue = $overlay[$key] ?? null;

    if (is_array($value)) {
        // Nested group
        ?>
        <fieldset class="settings-group">
            <legend><?= __('Setting: {path}', ['path' => $actualFullPath]) ?></legend>

            <?= $this->element('Settings/FormFields', [
                'data' => $value,
                'overlay' => is_array($overlayValue) ? $overlayValue : [],
                'path' => $actualPath,
                'fullPath' => $actualFullPath,
            ]) ?>
        </fieldset>

        <?php
    } else {
        // Scalar value
        $type = 'text';
        if (str_ends_with($key, '_text') || str_ends_with($key, '_html')) {
            $type = 'textarea';
        }
        ?>
        <div class="input text settings-field">
            <?= $this->Form->control("value.$actualPath", [
                'type' => $type,
                'value' => $overlayValue ?? '',
                'placeholder' => $value,
            ]) ?>

            <small class="default-value">
                <?= __('Default value:') ?>
                <pre><?= h($value) ?></pre>
                <br>
            </small>
        </div>
        <?php
    }
}
