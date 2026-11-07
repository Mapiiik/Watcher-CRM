<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Check\AbstractCheckRegistry;
use App\Check\CheckInterface;
use Dashboard\Card\AbstractDashboardCard;
use Override;

/**
 * How much each of one family of checks currently has to say.
 *
 * A line per check rather than a card per check: they are all the same kind of finding, and a card
 * each would push everything else off the dashboard. The counts link into the overview, which is
 * where the records themselves are.
 *
 * Which checks appear is decided by the checks, through `onDashboard()` - a check that is not a
 * list of faults keeps to the overview.
 */
abstract class AbstractProblemsCard extends AbstractDashboardCard
{
    use OverviewOfChecksTrait;

    /**
     * @param \App\Check\AbstractCheckRegistry<covariant \App\Check\CheckInterface> $checks Registry of the checks.
     */
    public function __construct(private AbstractCheckRegistry $checks)
    {
    }

    /**
     * The overview the card leads into.
     *
     * @return string
     */
    abstract protected function overviewAction(): string;

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['bookkeeper', 'sales-manager', 'sales-representative', 'manager'];
    }

    /**
     * Aggregates over everything on file are not something to make the dashboard wait for.
     *
     * @return bool
     */
    #[Override]
    public function deferred(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        $rows = [];
        foreach ($this->checks->forDashboard() as $check) {
            $total = $check->count();

            // A check earns a line by having found something. Most of them sit at zero most
            // of the time, and a column of zeroes is where the one that is not zero goes
            // unread.
            if ($total === 0) {
                continue;
            }

            $rows[] = [
                'title' => $check->title(),
                'total' => $total,
                'url' => $this->overviewUrl($check),
            ];
        }

        return ['rows' => $rows, 'overview_url' => $this->overviewUrl()];
    }

    /**
     * The overview, narrowed to one check where one is named.
     *
     * @param \App\Check\CheckInterface|null $only The check to show alone.
     * @return array<string, mixed>
     */
    private function overviewUrl(?CheckInterface $only = null): array
    {
        $checks = [];
        foreach ($this->checks->all() as $check) {
            $checks[$check->id()] = $only === null
                ? (int)!$check->optional()
                : (int)($check->id() === $only->id());
        }

        return $this->overviewOf($this->overviewAction(), $checks);
    }
}
