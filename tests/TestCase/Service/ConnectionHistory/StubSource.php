<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\ConnectionHistory;

use App\Model\Enum\ConnectionHistorySource;
use App\Service\ConnectionHistory\SourceInterface;
use Override;

/**
 * A source that reports whatever the test hands it.
 *
 * Standing in for the RADIUS source keeps these tests off the RADIUS database
 * entirely, which is the point of the sources being an interface: the merging
 * is application logic and can be checked without any accounting data at all.
 */
class StubSource implements SourceInterface
{
    /**
     * @param array<\App\Service\ConnectionHistory\ConnectionInterval> $intervals Intervals to report.
     * @param bool $available Whether the source claims to be reachable.
     */
    public function __construct(
        private array $intervals = [],
        private bool $available = true,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getSource(): ConnectionHistorySource
    {
        return ConnectionHistorySource::Radius;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getIntervals(): iterable
    {
        return $this->intervals;
    }
}
