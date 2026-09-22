<?php
declare(strict_types=1);

namespace App\RegulatoryReporting\Hr;

use App\Model\Entity\Billing;
use App\Model\Enum\AccessTechnology;
use App\Model\Table\BillingsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use DateTimeImmutable;
use PhpCollective\DecimalObject\Decimal;
use Radius\Model\Table\RadacctTable;

/**
 * What CRM can fill in of HAKOM's quarterly forms for retail internet access.
 *
 * The connections are counted as they stand on the last day of the quarter, by the contracted
 * download speed. The revenue is the quarter's share of the connections' billings, without VAT.
 * The traffic is estimated from RADIUS accounting. What the forms ask beyond that - the
 * investments, the staff - is not kept here and is filled in by hand.
 */
class HakomQuarterlyReport
{
    use LocatorAwareTrait;

    /**
     * Bytes in the form's terabyte.
     */
    private const TERABYTE = 1e12;

    /**
     * Connections by business, technology and band.
     *
     * @var array<int, array<string, array<int, int>>>
     */
    private array $connections = [];

    /**
     * Revenue without VAT by business and technology.
     *
     * @var array<int, array<string, float>>
     */
    private array $revenue = [];

    /**
     * Octets by technology.
     *
     * @var array<string, float>
     */
    private array $traffic = [];

    /**
     * Connections the form has no row for: too slow for its lowest band, or of a technology it
     * does not place.
     */
    public int $notPlaced = 0;

    /**
     * Octets of contracts without a connection of a known technology in the quarter.
     */
    public float $trafficNotPlaced = 0.0;

    /**
     * @param \Cake\I18n\Date $from First day of the quarter.
     * @param \Cake\I18n\Date $until Last day of the quarter.
     */
    public function __construct(public readonly Date $from, public readonly Date $until)
    {
    }

    /**
     * The report of a quarter.
     *
     * @param int $year The year.
     * @param int $quarter One to four.
     * @return self
     */
    public static function forQuarter(int $year, int $quarter): self
    {
        $from = new Date(sprintf('%d-%02d-01', $year, ($quarter - 1) * 3 + 1));

        return new self($from, $from->addMonths(2)->lastOfMonth());
    }

    /**
     * Counts and sums everything the rows are made of.
     *
     * @return $this
     */
    public function build()
    {
        /** @var list<\App\Model\Entity\Billing> $billings */
        $billings = $this->fetchTable(BillingsTable::class)->find()
            ->contain(['Customers' => ['AccountingProfiles'], 'Services' => ['ConnectionProfiles']])
            ->where(['ConnectionProfiles.access_technology IS NOT' => null])
            ->where(['Billings.billing_from <=' => $this->until])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $this->from,
                ],
            ])
            ->all()
            ->toList();

        $technologyOfContract = [];
        foreach ($billings as $billing) {
            /** @var \App\Model\Enum\AccessTechnology $technology */
            $technology = $billing->service?->connection_profile?->access_technology;
            $business = (int)$billing->customer->isBusiness();

            $this->revenue[$business][$technology->value] = ($this->revenue[$business][$technology->value] ?? 0.0)
                + $this->revenueOf($billing);

            $technologyOfContract[$billing->contract_id] = $technology;

            if (!$billing->isActiveOn($this->until)) {
                continue;
            }

            $band = HakomSpeedBand::fromKbps($billing->service?->connection_profile?->speed_down);
            if ($band === null || HakomTechnology::connections($technology) === null) {
                $this->notPlaced++;

                continue;
            }
            $this->connections[$business][$technology->value][$band->value] =
                ($this->connections[$business][$technology->value][$band->value] ?? 0) + 1;
        }

        $octets = $this->fetchTable(RadacctTable::class)->octetsByContract(
            new DateTimeImmutable($this->from->format('Y-m-d')),
            new DateTimeImmutable($this->until->addDays(1)->format('Y-m-d')),
        );
        foreach ($octets as $contract => $moved) {
            $technology = $technologyOfContract[$contract] ?? null;
            if ($technology === null) {
                $this->trafficNotPlaced += $moved;

                continue;
            }
            $this->traffic[$technology->value] = ($this->traffic[$technology->value] ?? 0.0) + $moved;
        }

        return $this;
    }

    /**
     * The form "Usluga pristupa Internetu - maloprodaja": the connections, the traffic and the
     * revenue, each row the form would have non-zero, and every band of a technology that is there.
     *
     * @return list<\App\RegulatoryReporting\Hr\HakomRow>
     */
    public function internetAccess(): array
    {
        $rows = [];

        foreach ([0, 1] as $business) {
            foreach ($this->connections[$business] ?? [] as $technology => $bands) {
                $branch = HakomTechnology::connections(AccessTechnology::from($technology));
                if ($branch === null) {
                    continue;
                }
                [$branch, $label] = $branch;
                foreach (HakomSpeedBand::cases() as $band) {
                    $rows[] = new HakomRow(
                        sprintf('1.1.%d.%s.%d', $business + 1, $branch, $band->value),
                        $label . ' - ' . $band->label() . ($business ? ' (poslovni)' : ' (privatni)'),
                        $bands[$band->value] ?? 0,
                        'kom',
                    );
                }
            }
        }

        $traffic = [];
        foreach ($this->traffic as $technology => $octets) {
            [$code, $label] = HakomTechnology::traffic(AccessTechnology::from($technology));
            $traffic[$code] = [$label, ($traffic[$code][1] ?? 0.0) + $octets];
        }
        foreach ($traffic as $code => [$label, $octets]) {
            $rows[] = new HakomRow($code, $label, round($octets / self::TERABYTE, 2), 'TB');
        }

        foreach ([0, 1] as $business) {
            $revenue = [];
            foreach ($this->revenue[$business] ?? [] as $technology => $sum) {
                [$code, $label] = HakomTechnology::revenue(AccessTechnology::from($technology));
                $code = sprintf('3.%d.%s', $business + 1, $code);
                $revenue[$code] = [$label, ($revenue[$code][1] ?? 0.0) + $sum];
            }
            foreach ($revenue as $code => [$label, $sum]) {
                $rows[] = new HakomRow($code, $label, round($sum, 2), 'EUR');
            }
        }

        return $rows;
    }

    /**
     * The form "Usluge i paketi usluga": internet access is sold on its own, so every user of it
     * and all its revenue go under the stand-alone service.
     *
     * @return list<\App\RegulatoryReporting\Hr\HakomRow>
     */
    public function servicesAndPackages(): array
    {
        $rows = [];
        foreach ([0, 1] as $business) {
            $users = 0;
            foreach ($this->connections[$business] ?? [] as $bands) {
                $users += array_sum($bands);
            }
            $rows[] = new HakomRow(
                sprintf('1.%d.1.1', $business + 1),
                'Samostalna usluga širokopojasnog pristupa internetu putem nepokretne mreže',
                $users,
                'kom',
            );
        }
        foreach ([0, 1] as $business) {
            $rows[] = new HakomRow(
                sprintf('2.%d.1.1', $business + 1),
                'Prihod od samostalne usluge širokopojasnog pristupa internetu putem nepokretne mreže',
                round(array_sum($this->revenue[$business] ?? []), 2),
                'EUR',
            );
        }

        return $rows;
    }

    /**
     * What a billing brought in over the quarter, month by month, without VAT.
     *
     * @param \App\Model\Entity\Billing $billing The billing.
     * @return float
     */
    private function revenueOf(Billing $billing): float
    {
        $total = 0.0;
        for ($month = $this->from; $month <= $this->until; $month = $month->addMonths(1)) {
            $total += $billing->periodTotal($month, $month->lastOfMonth())->toFloat();
        }

        return Billing::calcVatBaseFromTotal(
            Decimal::create($total),
            $billing->customer->accounting_profile->vat_rate,
        )->toFloat();
    }
}
