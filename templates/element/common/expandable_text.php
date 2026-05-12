<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $text
 * @var int $lines
 * @var bool $enabled
 * @var string $mode  start|end
 */

$text ??= '';
$lines ??= 5;
$enabled ??= true;
$mode ??= 'start'; // default

if ($enabled) :
    $more = '⇣ ' . __('more') . ' ⇣';
    $less = '⇡ ' . __('less') . ' ⇡';
    if ($mode === 'end') {
        $more = '⇡ ' . __('more') . ' ⇡';
        $less = '⇣ ' . __('less') . ' ⇣';
    }
    ?>
    <div class="expandable-text mode-<?= h($mode) ?>"
        data-more="<?= h($more) ?>"
        data-less="<?= h($less) ?>"
        data-lines="<?= $lines ?>">
        <?php if ($mode === 'end') : ?>
        <a class="toggle"><?= $more ?></a>
        <?php endif; ?>
        <div class="expandable-text-viewport">
            <div class="expandable-text-content">
                <?= nl2br(h($text)) ?>
            </div>
        </div>
        <?php if ($mode !== 'end') : ?>
        <a class="toggle"><?= $more ?></a>
        <?php endif; ?>
    </div>
    <?php
else :
    echo nl2br(h($text));
endif;
