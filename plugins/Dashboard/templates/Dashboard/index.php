<?php
/**
 * @var \App\View\AppView $this
 * @var list<\Dashboard\Card\DashboardCardInterface> $cards
 */

$this->assign('title', __d('dashboard', 'Dashboard'));
$this->Html->css('Dashboard.dashboard', ['block' => true]);
// The deferred cards are the only thing here that fetches itself, so the script comes with
// the page that needs it rather than with every page.
$this->Html->script('lazy-load.js', ['block' => true]);
?>
<div class="dashboard index content">
    <h3><?= __d('dashboard', 'Dashboard') ?></h3>

    <?php if ($cards === []) : ?>
        <p><?= __d('dashboard', 'There is nothing on the dashboard for your role yet.') ?></p>
    <?php else : ?>
        <div class="dashboard-cards">
            <?php foreach ($cards as $card) : ?>
                <?php if ($card->deferred()) : ?>
                    <div class="related">
                        <h4><?= h($card->title()) ?></h4>
                        <div
                            class="lazy-load"
                            data-url="<?= $this->Url->build(['action' => 'card', $card->id()]) ?>"
                            data-trigger="visible"
                        ><p><?= __d('dashboard', 'Loading…') ?></p></div>
                    </div>
                <?php else : ?>
                    <?= $this->element('Dashboard/frame', ['card' => $card]) ?>
                <?php endif ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
