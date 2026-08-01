<?php
declare(strict_types=1);

namespace App\Service\ConnectionHistory;

use App\Model\Enum\ConnectionHistorySource;

/**
 * A system able to tell where accounts have been connected over time.
 *
 * Implementations only read. They hand over intervals derived from whatever
 * they have left and take no part in deciding what gets stored, so a source
 * database may be replaced or go down without the recorded history noticing.
 */
interface SourceInterface
{
    /**
     * Which source the returned intervals belong to.
     *
     * @return \App\Model\Enum\ConnectionHistorySource
     */
    public function getSource(): ConnectionHistorySource;

    /**
     * Intervals derived from everything the source still holds.
     *
     * Ordered by account and then chronologically, which is what the updater
     * relies on when it walks them against the stored history.
     *
     * @return iterable<\App\Service\ConnectionHistory\ConnectionInterval>
     */
    public function getIntervals(): iterable;

    /**
     * Whether the source can be reached right now.
     *
     * A source that is down must not be treated as a source reporting nothing,
     * or a failed connection would look like every account having gone away.
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
