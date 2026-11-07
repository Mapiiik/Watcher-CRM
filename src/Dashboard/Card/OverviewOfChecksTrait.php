<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

/**
 * The link from a card into an overview of findings.
 */
trait OverviewOfChecksTrait
{
    /**
     * An overview with the checks as they are said to be.
     *
     * Every check has to be named, the ones left off as well: the overview reads a check that is
     * not in the query string as being at its default, so a link that only switched one on would
     * arrive with the rest switched on beside it.
     *
     * @param string $action The overview to open.
     * @param array<string, int> $checks Whether each check the overview knows is on.
     * @return array<string, mixed>
     */
    protected function overviewOf(string $action, array $checks): array
    {
        return [
            'controller' => 'Overviews',
            'action' => $action,
            'customer_id' => false,
            // A card counts only what is running, so the link has to say so - arriving at an
            // overview holding a different number than the card that led there reads as one of
            // the two being wrong.
            '?' => ['checks' => $checks, 'ignore_inactive' => 1],
        ];
    }
}
