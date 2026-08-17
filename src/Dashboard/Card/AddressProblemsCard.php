<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Addresses\Check\AddressCheckInterface;
use App\Addresses\Check\AddressCheckRegistry;
use Dashboard\Card\AbstractDashboardCard;
use Override;

/**
 * How much each of the address checks currently has to say.
 *
 * A line per check rather than a card per check: they are all the same kind of finding, and
 * four of them side by side would push everything else off the dashboard. The counts link
 * into the overview, which is where the records themselves are.
 *
 * Which checks appear is decided by the checks, through `onDashboard()` - a check that is
 * not a list of faults keeps to the overview.
 */
class AddressProblemsCard extends AbstractDashboardCard
{
    /**
     * @param \App\Addresses\Check\AddressCheckRegistry $checks Registry of address checks.
     */
    public function __construct(private AddressCheckRegistry $checks)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'address_problems';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Address Problems');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['bookkeeper', 'sales-manager', 'sales-representative'];
    }

    /**
     * Four aggregates over the whole customer base come to some tens of milliseconds, which
     * is not something to make the dashboard wait for.
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
        $checks = $this->checks->forDashboard();

        $rows = [];
        foreach ($checks as $check) {
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
     * Every other check is named as off rather than left out. The overview reads a check
     * that is not in the query string as being at its default, so a link that only switched
     * one on would arrive with the rest switched on beside it.
     *
     * @param \App\Addresses\Check\AddressCheckInterface|null $only The check to show alone.
     * @return array<string, mixed>
     */
    private function overviewUrl(?AddressCheckInterface $only = null): array
    {
        $checks = [];
        foreach ($this->checks->all() as $check) {
            $checks[$check->id()] = $only === null
                ? (int)!$check->optional()
                : (int)($check->id() === $only->id());
        }

        return [
            'controller' => 'Overviews',
            'action' => 'overviewOfAddressProblems',
            'customer_id' => false,
            // The card counts only what is running, so the link has to say so - arriving at
            // an overview holding a different number than the card that led there reads as
            // one of the two being wrong.
            '?' => ['checks' => $checks, 'ignore_inactive' => 1],
        ];
    }
}
