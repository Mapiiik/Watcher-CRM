<?php
declare(strict_types=1);

/**
 * Recursive form field generator for settings.
 *
 * @var \App\View\AppView $this
 * @var array  $default  Default values (nested array)
 * @var array  $overlay  Overlay values from DB (nested array)
 * @var string $path     Current dot-path (e.g. "invoices.phone")
 * @var string $fullPath Current full-path (e.g. "core.company.invoices.phone")
 */
?>
<?php
foreach ($default as $key => $defaultValue) {
    $actualFullPath = ltrim($fullPath . '.' . $key, '.');
    $actualPath = ltrim($path . '.' . $key, '.');
    $actualOverlay = $overlay[$key] ?? null;

    if (is_array($defaultValue)) {
        // Nested group
        ?>
        <fieldset class="settings-group">
            <legend><?= __('Setting: {path}', ['path' => $actualFullPath]) ?></legend>

            <?= $this->element('Settings/FormFields', [
                'default' => $defaultValue,
                'overlay' => is_array($actualOverlay) ? $actualOverlay : [],
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
            <?= $this->Form->control("overlay.$actualPath", [
                'type' => $type,
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
