<?php
/**
 * Start of an interval, marked when it is only a lower bound.
 *
 * An interval opened from the oldest accounting record still available says
 * nothing about how long the account had already been there, and one opened
 * because the account was moved is accurate to the update run at worst. Both
 * would otherwise read as exact dates.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\HistoricalConnection $interval
 */

if ($interval->first_seen_exact) {
    echo h($interval->first_seen);

    return;
}
?>
<span class="approximate" title="<?= h($interval->first_seen_source->label()) ?>"><?=
    __('{0} or earlier', h($interval->first_seen))
?></span>
