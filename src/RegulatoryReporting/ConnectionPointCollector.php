<?php
declare(strict_types=1);

namespace App\RegulatoryReporting;

use App\Addresses\Resolver as AddressesResolver;
use App\Model\Entity\Billing;
use App\Model\Enum\AccessTechnology;
use App\Model\Table\BillingsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Closure;
use RuntimeException;

/**
 * Gathers the address points a regulator's report is made of.
 *
 * What is common to them all: the connections active in a month, at an address the national
 * registry knows, sorted into the regulator's categories and put together by address point. Which
 * categories there are, and what is reported about each point, is up to the report.
 */
class ConnectionPointCollector
{
    use LocatorAwareTrait;

    /**
     * What went wrong with the addresses on the way, for the operator to be told.
     *
     * @var list<string>
     */
    private array $problems = [];

    /**
     * @param \Closure(\App\Model\Enum\AccessTechnology): ?string $categoryOf The regulator's
     *      category of a technology, null when it reports nothing of it.
     */
    public function __construct(private readonly Closure $categoryOf)
    {
    }

    /**
     * The points of the month, by category and then by "source|reference", both in order.
     *
     * @param \Cake\I18n\Date $month Any day of the month.
     * @param string $registrySource The country's registry, the report being about one country.
     * @return array<string, array<string, \App\RegulatoryReporting\ConnectionPoint>>
     */
    public function collect(Date $month, string $registrySource): array
    {
        $this->problems = [];

        /** @var list<\App\Model\Entity\Billing> $billings */
        $billings = $this->fetchTable(BillingsTable::class)->find()
            ->contain('Customers')
            ->contain(['Contracts' => ['InstallationAddresses']])
            ->contain(['Services' => ['ServiceTypes', 'ConnectionProfiles']])
            ->where(['Billings.billing_from <=' => $month->lastOfMonth()])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $month->firstOfMonth(),
                ],
            ])
            ->where(['ConnectionProfiles.speed_down IS NOT NULL'])
            ->where(['ConnectionProfiles.speed_up IS NOT NULL'])
            ->where(['ConnectionProfiles.access_technology IN' => $this->reportedTechnologies()])
            ->where(['InstallationAddresses.address_registry_reference IS NOT NULL'])
            ->where(['InstallationAddresses.address_registry_source' => $registrySource])
            ->orderBy(['InstallationAddresses.address_registry_reference'])
            ->all()
            ->toList();

        $points = [];
        foreach ($billings as $billing) {
            $category = $this->categoryOfBilling($billing);
            $address = $billing->contract->installation_address;
            $key = $address->address_registry_source . '|' . $address->address_registry_reference;

            $points[$category][$key] ??= new ConnectionPoint(
                $category,
                (string)$address->address_registry_source,
                (string)$address->address_registry_reference,
            );
            $points[$category][$key]->billings[] = $billing;
        }
        ksort($points);

        $this->resolve($points, $billings);

        return $points;
    }

    /**
     * What went wrong with the addresses in the last collection.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        return $this->problems;
    }

    /**
     * The technologies the regulator has a category for.
     *
     * @return list<string>
     */
    private function reportedTechnologies(): array
    {
        $technologies = [];
        foreach (AccessTechnology::cases() as $technology) {
            if (($this->categoryOf)($technology) !== null) {
                $technologies[] = $technology->value;
            }
        }

        return $technologies;
    }

    /**
     * The category of a billing the query let through, which therefore has one.
     *
     * @param \App\Model\Entity\Billing $billing A connection.
     * @return string
     */
    private function categoryOfBilling(Billing $billing): string
    {
        /** @var \App\Model\Enum\AccessTechnology $technology */
        $technology = $billing->service?->connection_profile?->access_technology;

        return (string)($this->categoryOf)($technology);
    }

    /**
     * Puts the registry's own reference and wording on every point.
     *
     * A reference the registry does not know is reported empty and the contracts behind it named,
     * so that somebody puts the address right. When the registry cannot be asked at all, the
     * reference on file is all there is to report.
     *
     * @param array<string, array<string, \App\RegulatoryReporting\ConnectionPoint>> $points The points.
     * @param list<\App\Model\Entity\Billing> $billings The connections behind them.
     * @return void
     */
    private function resolve(array $points, array $billings): void
    {
        try {
            $matches = AddressesResolver::matchMap(array_values(array_filter(array_map(
                fn(Billing $billing) => $billing->contract->installation_address,
                $billings,
            ))));
        } catch (RuntimeException $e) {
            $matches = [];
            $this->problems[] = __(
                'Could not retrieve addresses from national address registry: {0}',
                $e->getMessage(),
            );
        }

        foreach ($points as $byAddress) {
            foreach ($byAddress as $key => $point) {
                $match = $matches[$key] ?? null;

                if ($match !== null) {
                    $point->reportedReference = $match->registryReference;
                    $point->formattedAddress = $match->formattedAddress;
                } elseif ($matches !== []) {
                    $this->problems[] = __(
                        'Invalid RUIAN GID: {0} for addresses associated with contracts: {1}',
                        $point->registryReference,
                        implode(', ', $point->contractNumbers()),
                    );
                } else {
                    $point->reportedReference = $point->registryReference;
                }
            }
        }
    }
}
