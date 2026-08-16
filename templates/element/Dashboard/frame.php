<?php
/**
 * One card, heading and body together, drawn as the grouped blocks elsewhere are.
 *
 * @var \App\View\AppView $this
 * @var \App\Dashboard\Card\DashboardCardInterface $card
 */
?>
<div class="related">
    <h4><?= h($card->title()) ?></h4>
    <?= $this->element('Dashboard/' . $card->template(), $card->data()) ?>
</div>
