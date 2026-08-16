<?php
/**
 * The body of one deferred card, fetched on its own request. The heading is already on
 * the page, so only what goes below it is rendered here.
 *
 * @var \App\View\AppView $this
 * @var \Dashboard\Card\DashboardCardInterface $card
 */
?>
<?= $this->element('Dashboard/' . $card->template(), $card->data());
