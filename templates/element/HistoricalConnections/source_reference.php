<?php
/**
 * What the interval is filed under in the system it came from.
 *
 * The wording of what the reference names comes from the source itself, so a
 * column headed "Source Reference" reads correctly whatever the source is and
 * nothing here has to be touched when another one appears.
 *
 * The reference is kept as text of its own so the history stays readable after
 * the account has been renamed or deleted on the far side. The link is only
 * offered on top of it, and only where the account is still known and the
 * reader is allowed to open it.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\HistoricalConnection $interval
 */

use App\Model\Enum\HistoricalConnectionSource;

$reference = h($interval->source_reference);

if ($interval->source === HistoricalConnectionSource::Radius && $interval->account_id !== null) {
    $link = $this->AuthLink->link(
        $interval->source_reference,
        [
            'plugin' => 'Radius',
            'controller' => 'Accounts',
            'action' => 'view',
            $interval->account_id,
        ],
    );

    if ($link !== '') {
        $reference = $link;
    }
}

echo h($interval->source->referenceLabel()) . ': ' . $reference;
