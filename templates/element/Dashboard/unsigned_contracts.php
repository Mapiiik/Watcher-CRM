<?php
/**
 * @var \App\View\AppView $this
 * @var int $waiting
 * @var int $notifying
 * @var int $blocking
 * @var array<string, mixed> $url
 */

// What is worth showing, widest first. A slice at zero is left out rather than drawn as a
// nought: three figures where two of them say nothing is harder to read at a glance than the
// one figure that does.
$slices = [
    [$waiting, __('in effect and unsigned, still inside every deadline')],
    [$notifying, __('past the reminder deadline')],
    [$blocking, __('past the disconnection deadline')],
];
?>
<?php if ($waiting === 0 && $notifying === 0 && $blocking === 0) : ?>
    <p><?= __('Every running service has paper behind it.') ?></p>
<?php else : ?>
    <?php foreach ($slices as [$count, $caption]) : ?>
        <?php if ($count > 0) : ?>
            <p class="dashboard-figure"><?= h((string)$count) ?></p>
            <p><?= h($caption) ?></p>
        <?php endif ?>
    <?php endforeach ?>
    <p><?= $this->Html->link(__('Open the contract problems'), $url) ?></p>
<?php endif ?>
