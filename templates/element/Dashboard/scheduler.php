<?php
/**
 * The scheduler card. The plugin words all of it, so nothing here is translated.
 *
 * @var \App\View\AppView $this
 * @var \Scheduler\Model\Enum\SchedulerState $state
 * @var string $summary
 * @var list<string> $problems
 * @var list<array{title: string, url: array<string, mixed>}> $links
 */

use Scheduler\Model\Enum\SchedulerState;

?>
<?php if ($state === SchedulerState::Attention) : ?>
    <div class="message warning" role="alert"><?= h($summary) ?></div>
    <ul>
        <?php foreach ($problems as $problem) : ?>
            <li><?= h($problem) ?></li>
        <?php endforeach ?>
    </ul>
<?php elseif ($state === SchedulerState::Unknown) : ?>
    <p class="dashboard-hint"><?= h($summary) ?></p>
<?php else : ?>
    <p><?= h($summary) ?></p>
<?php endif ?>
<p>
    <?php foreach ($links as $i => $link) : ?>
        <?= $i > 0 ? ' · ' : '' ?><?= $this->Html->link($link['title'], $link['url']) ?>
    <?php endforeach ?>
</p>
