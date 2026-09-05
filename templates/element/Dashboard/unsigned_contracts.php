<?php
/**
 * @var \App\View\AppView $this
 * @var int $waiting
 * @var int $notifying
 * @var int $blocking
 * @var array<string, mixed> $url
 * @var int $unanswered
 * @var array<string, mixed> $unanswered_url
 * @var int $unsent
 * @var array<string, mixed> $unsent_url
 */

// What is worth showing, widest first. A slice at zero is left out rather than drawn as a
// nought: three figures where two of them say nothing is harder to read at a glance than the
// one figure that does.
$slices = [
    [$waiting, __('in effect and unsigned, still inside every deadline')],
    [$notifying, __('past the reminder deadline')],
    [$blocking, __('past the disconnection deadline')],
];

// The proposals, each with the listing it belongs to. They are counted apart from the three
// above and link somewhere else, so they carry their own link rather than share the one below.
$papers = [
    [$unsent, __('drawn up and never sent'), $unsent_url, __('Open the proposals nobody has sent')],
    [
        $unanswered,
        __('sent to the customer and still not signed'),
        $unanswered_url,
        __('Open the proposals waiting for a signature'),
    ],
];
?>
<?php if ($waiting === 0 && $notifying === 0 && $blocking === 0 && $unsent === 0 && $unanswered === 0) : ?>
    <p><?= __('Every running service has paper behind it, and nothing is waiting to go out.') ?></p>
<?php else : ?>
    <?php if ($waiting > 0 || $notifying > 0 || $blocking > 0) : ?>
        <?php foreach ($slices as [$count, $caption]) : ?>
            <?php if ($count > 0) : ?>
                <p class="dashboard-figure"><?= h((string)$count) ?></p>
                <p><?= h($caption) ?></p>
            <?php endif ?>
        <?php endforeach ?>
        <p><?= $this->Html->link(__('Open the contract problems'), $url) ?></p>
    <?php endif ?>
    <?php foreach ($papers as [$count, $caption, $where, $saying]) : ?>
        <?php if ($count > 0) : ?>
            <p class="dashboard-figure"><?= h((string)$count) ?></p>
            <p><?= h($caption) ?></p>
            <p><?= $this->Html->link($saying, $where) ?></p>
        <?php endif ?>
    <?php endforeach ?>
<?php endif ?>
