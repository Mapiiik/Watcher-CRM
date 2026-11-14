<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Enum\ContractPeriodSource;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractsTable;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Dashboard\Card\AbstractDashboardCard;
use Override;
use Settings\Utility\Settings;

/**
 * What this month and the last did to the file: contracts and customers in and out, and the
 * connections running.
 *
 * Whole months, as the overview of new and ending contracts reads them, so a number followed
 * into that overview comes out the same there. This month therefore already counts what is
 * dated to end later in it.
 */
class StatisticsCard extends AbstractDashboardCard
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param \App\Model\Table\BillingsTable $billings Billings table.
     */
    public function __construct(private ContractsTable $contracts, private BillingsTable $billings)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'statistics';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Statistics');
    }

    /**
     * Whoever may open the overview the numbers lead to.
     *
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['bookkeeper', 'sales-manager', 'sales-representative', 'manager'];
    }

    /**
     * Fetched on its own, as every number on it is a count over the whole file.
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
        $source = ContractPeriodSource::fromQuery(Settings::getString(
            self::SETTINGS_PATH . '.contracts.period_source',
            ContractPeriodSource::Versions->value,
        ));

        $thisMonth = Date::today()->firstOfMonth();
        $months = [$thisMonth, $thisMonth->subMonths(1)];

        $starting = [];
        $ending = [];
        $newCustomers = [];
        $departedCustomers = [];
        $connections = [];
        $urls = [];

        foreach ($months as $month) {
            $from = $month->firstOfMonth();
            $to = $month->lastOfMonth();

            $starting[] = $this->count('startingBetween', $source, $from, $to);
            $ending[] = $this->count('endingBetween', $source, $from, $to);
            $newCustomers[] = $this->count('customersStartingBetween', $source, $from, $to);
            $departedCustomers[] = $this->count('customersEndingBetween', $source, $from, $to);
            // today for the month that is running, as nobody knows yet how it ends
            $connections[] = $this->activeConnections($month === $thisMonth ? Date::today() : $to);
            $urls[] = [
                'controller' => 'Overviews',
                'action' => 'overviewOfNewAndEndingContracts',
                'customer_id' => false,
                '?' => ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'source' => $source->value],
            ];
        }

        return [
            'months' => $months,
            'source' => $source,
            'rows' => [
                ['title' => __('New Contracts'), 'values' => $starting, 'urls' => $urls],
                ['title' => __('Ended Contracts'), 'values' => $ending, 'urls' => $urls],
                [
                    'title' => __('Net Change'),
                    'values' => array_map(fn(int $in, int $out): int => $in - $out, $starting, $ending),
                    'signed' => true,
                ],
                ['title' => __('New Customers'), 'values' => $newCustomers],
                ['title' => __('Departed Customers'), 'values' => $departedCustomers],
                ['title' => __('Active Connections'), 'values' => $connections],
            ],
        ];
    }

    /**
     * How many contracts, or customers, one of the period finders finds.
     *
     * @param string $finder The finder.
     * @param \App\Model\Enum\ContractPeriodSource $source Which dates the period is read from.
     * @param \Cake\I18n\Date $from First day of the period.
     * @param \Cake\I18n\Date $to Last day of the period.
     * @return int
     */
    private function count(string $finder, ContractPeriodSource $source, Date $from, Date $to): int
    {
        return $this->contracts->find($finder, source: $source, from: $from, to: $to)->count();
    }

    /**
     * Contracts with a connection billed on the given day. A connection is a service with a
     * connection profile, as the minimum price of a connection reads it.
     *
     * @param \Cake\I18n\Date $on The day.
     * @return int
     * @see \App\Contracts\MinimumConnectionPrice::isConnection()
     */
    private function activeConnections(Date $on): int
    {
        return $this->billings
            ->find('activeOn', on: $on)
            ->innerJoinWith(
                'Services',
                fn(SelectQuery $query): SelectQuery => $query->where(['Services.connection_profile_id IS NOT' => null]),
            )
            ->select(['Billings.contract_id'])
            ->distinct()
            ->count();
    }
}
