<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\Check\AddressCheckRegistry;
use App\Addresses\Resolver as AddressesResolver;
use App\Contracts\Check\ContractCheckRegistry;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Customers\Check\CustomerCheckRegistry;
use App\Model\Entity\Billing;
use App\Model\Entity\Commission;
use App\Model\Entity\Contract;
use App\Model\Entity\Service;
use App\Model\Enum\AccessTechnology;
use App\Model\Enum\ContractPeriodSource;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\DealerCommissionsTable;
use App\Model\Table\LabelsTable;
use App\Model\Table\ServicesTable;
use App\RegulatoryReporting\ConnectionPointCollector;
use App\RegulatoryReporting\Cz\CtuAdvertisedSpeedBand;
use App\RegulatoryReporting\Cz\CtuConnectionPointRow;
use App\RegulatoryReporting\Cz\CtuConnectionPointsCsv;
use App\RegulatoryReporting\Cz\CtuTechnologyCategory;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validation;
use PhpCollective\DecimalObject\Decimal;
use RuntimeException;
use stdClass;

/**
 * Overviews Controller
 */
class OverviewsController extends AppController
{
    use CommonViewVarListsTrait;

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
    }

    /**
     * Overview of contracts method
     *
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfContracts(): void
    {
        // get contracts table
        $contractsTable = $this->fetchTable(ContractsTable::class);

        // load labels
        $labelsTable = $this->fetchTable(LabelsTable::class);

        $labels = $labelsTable->find('list', order: [
            'name',
        ])->all();

        // Load addresses from national address registry for existing installation addresses
        /** @var \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Address> $installationAddresses */
        $installationAddresses = $contractsTable->InstallationAddresses
            ->find()
            ->where([
                'address_registry_source IS NOT' => null,
                'address_registry_reference IS NOT' => null,
            ])
            ->all();

        $registryAddresses = [];
        try {
            $registryAddresses = AddressesResolver::dropdownMap($installationAddresses);
        } catch (RuntimeException $e) {
            $this->Flash->warning(__(
                'Could not retrieve addresses from national address registry: {0}',
                $e->getMessage(),
            ));
        }

        // contracts filter
        $contractsFilter = [];

        // filter by labels
        if (!empty($this->getRequest()->getQuery('label_ids'))) {
            $uuidLabels = [];
            if (is_array($this->getRequest()->getQuery('label_ids'))) {
                foreach ($this->getRequest()->getQuery('label_ids') as $labelId) {
                    if (is_string($labelId) && Validation::uuid($labelId)) {
                        $uuidLabels[] = sprintf("'%s'::uuid", $labelId);
                    }
                }
            }

            $contractsFilter[] = [
                'Customers.id IN ('
                . ' SELECT customer_id FROM customer_labels '
                . 'GROUP BY customer_id '
                . 'HAVING array_agg(label_id) @> ARRAY[' . implode(',', $uuidLabels) . ']'
                . ')',
            ];

            unset($uuidLabels);
        }

        // filter by not labels
        if (!empty($this->getRequest()->getQuery('not_label_ids'))) {
            $uuidLabels = [];
            if (is_array($this->getRequest()->getQuery('not_label_ids'))) {
                foreach ($this->getRequest()->getQuery('not_label_ids') as $labelId) {
                    if (is_string($labelId) && Validation::uuid($labelId)) {
                        $uuidLabels[] = sprintf("'%s'::uuid", $labelId);
                    }
                }
            }

            $contractsFilter[] = [
                'Customers.id NOT IN (
                    SELECT customer_id FROM customer_labels
                    WHERE label_id = ANY(ARRAY[' . implode(',', $uuidLabels) . '])
                )',
            ];

            unset($uuidLabels);
        }

        // filter by access technology
        $accessTechnology = AccessTechnology::tryFrom((string)$this->getRequest()->getQuery('access_technology'));
        if ($accessTechnology !== null) {
            $filterQuery = $contractsTable->Billings->find()
                ->select([
                    'Billings.contract_id',
                ])
                ->innerJoinWith('Services')
                ->innerJoinWith('Services.ConnectionProfiles')
                ->distinct()
                ->where([
                    'ConnectionProfiles.access_technology' => $accessTechnology,
                ]);

            $contractsFilter[] = [
                'Contracts.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        // contracts query
        $contractsQuery = $contractsTable
            ->find()
            ->contain('ContractStates')
            ->contain('ServiceTypes')
            ->contain('InstallationAddresses')
            // The listing is ordered by columns of the customers, while the billings are eager
            // loaded per contract. The `subquery` strategy would reduce this query to
            // `SELECT Contracts.id ... GROUP BY Contracts.id` with that ORDER BY kept, which
            // PostgreSQL rejects - the ordered columns are not functionally dependent on the
            // contract. The emails and phones are loaded the same way, so that they survive
            // sorting by any of the contract columns the listing offers as well.
            ->contain([
                'Billings' => [
                    'strategy' => Association::STRATEGY_SELECT,
                    'Services' => [
                        'ConnectionProfiles',
                    ],
                ],
                'Customers' => [
                    'Emails' => [
                        'strategy' => Association::STRATEGY_SELECT,
                    ],
                    'Phones' => [
                        'strategy' => Association::STRATEGY_SELECT,
                    ],
                ],
            ])
            ->where($contractsFilter);

        // filter by contract state
        $contractStateId = $this->getRequest()->getQuery('contract_state_id');
        if (is_string($contractStateId) && Validation::uuid($contractStateId)) {
            $contractsQuery->where(['Contracts.contract_state_id' => $contractStateId]);
        }
        unset($contractStateId);

        // filter by service type
        $serviceTypeId = $this->getRequest()->getQuery('service_type_id');
        if (is_string($serviceTypeId) && Validation::uuid($serviceTypeId)) {
            $contractsQuery->where(['Contracts.service_type_id' => $serviceTypeId]);
        }
        unset($serviceTypeId);

        // filter by access point
        $accessPointId = $this->getRequest()->getQuery('access_point_id');
        if (is_string($accessPointId) && Validation::uuid($accessPointId)) {
            $contractsQuery->where(['Contracts.access_point_id' => $accessPointId]);
        }
        unset($accessPointId);

        // filter by registry address
        $registryAddressId = $this->getRequest()->getQuery('registry_address_id');
        if (is_string($registryAddressId)) {
            // expect format "source|reference", e.g. "cz|12345678"
            [
                $address_registry_source,
                $address_registry_reference,
            ] = explode('|', $registryAddressId, limit: 2) + [null, null];

            $contractsQuery->where([
                'InstallationAddresses.address_registry_reference' => $address_registry_reference,
                'InstallationAddresses.address_registry_source' => $address_registry_source,
            ]);
        }
        unset($registryAddressId);

        // load contracts with paginator
        /** @var iterable<\App\Model\Entity\Contract> $contracts */
        $contracts = $this->paginate($contractsQuery, [
            'sortableFields' => [
                'Customers.company',
                'Customers.last_name',
                'Customers.first_name',
                'Customers.nid',
                'number',
                'contract_state_id',
                'service_type_id',
                'installation_address_id',
                'vip',
                'access_point_id',
                'installation_date',
                'uninstallation_date',
                'termination_date',
            ],
            'order' => [
                'Customers.company' => 'ASC',
                'Customers.last_name' => 'ASC',
                'Customers.first_name' => 'ASC',
            ],
            'maxLimit' => PHP_INT_MAX,
        ]);

        $this->set(compact(
            'labels',
            'registryAddresses',
            'contracts',
        ));

        $this->setContractStatesViewVarList();
        $this->setServiceTypesViewVarList();
        $this->setAccessTechnologiesViewVarList();
        $this->setAccessPointsViewVarList();
    }

    /**
     * Overview of contracts beginning and ending within a period.
     *
     * Two listings rather than one, because the question behind them is what the month did
     * to the file: what came in, what went out, and which way the difference ran. They are
     * not halves of anything - a contract signed and given up inside the same period is in
     * both, and the net change is still the arithmetic it looks like.
     *
     * @return void Renders view
     */
    public function overviewOfNewAndEndingContracts(): void
    {
        $source = ContractPeriodSource::fromQuery($this->getRequest()->getQuery('source'));

        $today = new Date('now');
        $from = $this->queryDate('from') ?? $today->firstOfMonth();
        $to = $this->queryDate('to') ?? $today->lastOfMonth();

        // Left the way round it was asked rather than swapped: the form would otherwise show
        // one period while the tables below answered about another, which is worse than two
        // empty tables and a word saying why.
        if ($from > $to) {
            $this->Flash->warning(__('The period ends before it begins, so nothing falls inside it.'));
        }

        $starting = $this->contractsInPeriod('startingBetween', $source, $from, $to)->all();
        $ending = $this->contractsInPeriod('endingBetween', $source, $from, $to)->all();

        $this->set(compact('starting', 'ending', 'source', 'from', 'to'));

        $this->setContractStatesViewVarList();
        $this->setServiceTypesViewVarList();
        $this->setInstallationCitiesViewVarList();
    }

    /**
     * A date the query string names, where what it names is a date at all.
     *
     * @param string $name The query string key to read.
     * @return \Cake\I18n\Date|null
     */
    private function queryDate(string $name): ?Date
    {
        $value = $this->getRequest()->getQuery($name);

        return is_string($value) && Validation::date($value) ? new Date($value) : null;
    }

    /**
     * One of the two listings, with the filters and containments that apply to both.
     *
     * Built afresh for each of them: a query holds on to what it fetched, so one cannot be
     * asked two questions.
     *
     * @param string $finder Which end of the contract's life the period is asked about.
     * @param \App\Model\Enum\ContractPeriodSource $source Which dates that is read from.
     * @param \Cake\I18n\Date $from First day of the period, counted in.
     * @param \Cake\I18n\Date $to Last day of the period, counted in.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract>
     */
    private function contractsInPeriod(
        string $finder,
        ContractPeriodSource $source,
        Date $from,
        Date $to,
    ): SelectQuery {
        // Said outright: the finder is named by a variable, and the type of what comes back
        // cannot be read off a string.
        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract> $query */
        $query = $this->fetchTable(ContractsTable::class)
            ->find($finder, source: $source, from: $from, to: $to)
            // Contained rather than joined: all four are shown, and every one of them is a
            // `belongsTo`, so containing them costs the one join that filtering on them
            // would have cost anyway.
            ->contain(['Customers', 'ServiceTypes', 'ContractStates', 'InstallationAddresses'])
            // Appended after the date the finder ordered by, so the listing reads in the
            // order the period ran and ties come back the same way twice.
            ->orderBy([
                'Customers.company' => 'ASC',
                'Customers.last_name' => 'ASC',
                'Customers.first_name' => 'ASC',
            ]);

        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        if (is_string($service_type_id) && Validation::uuid($service_type_id)) {
            $query->where(['Contracts.service_type_id' => $service_type_id]);
        }

        $contract_state_id = $this->getRequest()->getQuery('contract_state_id');
        if (is_string($contract_state_id) && Validation::uuid($contract_state_id)) {
            $query->where(['Contracts.contract_state_id' => $contract_state_id]);
        }

        // The empty string is what the form helper puts beside a multiple select so that a
        // form clearing one says so. It is not a town, and passed on as one it would match
        // nothing and empty both tables.
        $cities = array_values(array_filter(
            (array)$this->getRequest()->getQuery('cities', []),
            static fn(mixed $city): bool => is_string($city) && $city !== '',
        ));
        if ($cities !== []) {
            // Bound rather than spelled into the SQL the way the label filter above does:
            // that one gets away with it by checking every value is a UUID first, and a town
            // is whatever somebody typed.
            $query->where(['InstallationAddresses.city IN' => $cities]);
        }

        return $query;
    }

    /**
     * Overview of billings beginning and ending within a period.
     *
     * The contracts' counterpart one level down: a contract that stays may still change what
     * it is charged, and that shows only on its billings. Each listing is summed, so the month's
     * effect on what gets invoiced reads straight off the page.
     *
     * @return void Renders view
     */
    public function overviewOfNewAndEndingBillings(): void
    {
        $today = new Date('now');
        $from = $this->queryDate('from') ?? $today->firstOfMonth();
        $to = $this->queryDate('to') ?? $today->lastOfMonth();

        if ($from > $to) {
            $this->Flash->warning(__('The period ends before it begins, so nothing falls inside it.'));
        }

        $starting = $this->billingsInPeriod('Billings.billing_from', $from, $to)->all();
        $ending = $this->billingsInPeriod('Billings.billing_until', $from, $to)->all();

        $sum = static fn(iterable $billings): Decimal => (new Collection($billings))->reduce(
            static fn(Decimal $total, Billing $billing): Decimal => $total->add($billing->total_price),
            Decimal::create(0),
        );
        $startingTotal = $sum($starting);
        $endingTotal = $sum($ending);

        $services = $this->fetchTable(ServicesTable::class)
            ->find('list')
            ->orderBy(['Services.name' => 'ASC'])
            ->all();

        $this->set(compact('starting', 'ending', 'startingTotal', 'endingTotal', 'from', 'to', 'services'));

        $this->setContractStatesViewVarList();
        $this->setServiceTypesViewVarList();
        $this->setInstallationCitiesViewVarList();
    }

    /**
     * One of the two listings of billings, with the filters that apply to both.
     *
     * @param string $field The day the period is asked about, `billing_from` or `billing_until`.
     * @param \Cake\I18n\Date $from First day of the period, counted in.
     * @param \Cake\I18n\Date $to Last day of the period, counted in.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Billing>
     */
    private function billingsInPeriod(string $field, Date $from, Date $to): SelectQuery
    {
        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Billing> $query */
        $query = $this->fetchTable(BillingsTable::class)
            ->find()
            ->contain([
                'Customers',
                'Services' => ['ServiceTypes'],
                'Contracts' => ['ContractStates', 'InstallationAddresses'],
            ])
            ->where([$field . ' >=' => $from, $field . ' <=' => $to])
            ->orderBy([
                $field => 'ASC',
                'Customers.company' => 'ASC',
                'Customers.last_name' => 'ASC',
                'Customers.first_name' => 'ASC',
            ]);

        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        if (is_string($service_type_id) && Validation::uuid($service_type_id)) {
            $query->where(['Services.service_type_id' => $service_type_id]);
        }

        $service_id = $this->getRequest()->getQuery('service_id');
        if (is_string($service_id) && Validation::uuid($service_id)) {
            $query->where(['Billings.service_id' => $service_id]);
        }

        $contract_state_id = $this->getRequest()->getQuery('contract_state_id');
        if (is_string($contract_state_id) && Validation::uuid($contract_state_id)) {
            $query->where(['Contracts.contract_state_id' => $contract_state_id]);
        }

        // Only a separate invoice is a question worth asking - the empty choice means both.
        $separate_invoice = $this->getRequest()->getQuery('separate_invoice');
        if ($separate_invoice === '0' || $separate_invoice === '1') {
            $query->where(['Billings.separate_invoice' => $separate_invoice === '1']);
        }

        // The empty string beside a multiple select is not a town, as on the contracts above.
        $cities = array_values(array_filter(
            (array)$this->getRequest()->getQuery('cities', []),
            static fn(mixed $city): bool => is_string($city) && $city !== '',
        ));
        if ($cities !== []) {
            $query->where(['InstallationAddresses.city IN' => $cities]);
        }

        return $query;
    }

    /**
     * Overview of active services method
     *
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfActiveServices(): void
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));
        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        $access_technology = AccessTechnology::tryFrom((string)$this->getRequest()->getQuery('access_technology'));
        $access_point_id = $this->getRequest()->getQuery('access_point_id');

        $this->set('show_billings', $this->getRequest()->getQuery('show_billings') == '1');

        $servicesQuery = $this->fetchTable(ServicesTable::class)
            ->find()
            ->contain('Billings', function (SelectQuery $q) use ($month_to_display, $access_point_id): SelectQuery {
                $q
                    ->contain('Services')
                    ->contain('Customers')
                    ->contain('Contracts', function (SelectQuery $q) use ($access_point_id) {
                        $q->contain('ContractStates');
                        // filter by access point
                        return is_string($access_point_id) && Validation::uuid($access_point_id) ?
                            $q->where(['Contracts.access_point_id' => $access_point_id]) :
                            $q;
                    });

                return $this->applyActiveInMonthScope($q, $month_to_display);
            })
            ->contain('ConnectionProfiles')
            ->contain('ServiceTypes')
            ->formatResults(
                function (CollectionInterface $services): CollectionInterface {
                    $services = $services->map(function (Service $service): Service {
                        $billings = new Collection($service->billings);

                        $service->set(
                            'number_of_uses',
                            $billings->sumOf('quantity'),
                        );

                        $service->set(
                            'number_of_uses_nonbusiness',
                            $billings
                                ->match(['customer.identity_number' => null])
                                ->sumOf('quantity'),
                        );

                        $service->set(
                            'sum',
                            $billings
                                ->sumOf(
                                    function (Billing $billing) {
                                        return $billing->sum->toFloat();
                                    },
                                ),
                        );

                        $service->set(
                            'fixed_discount_sum',
                            $billings
                                ->sumOf(
                                    function (Billing $billing) {
                                        return $billing->fixed_discount_sum->toFloat();
                                    },
                                ),
                        );

                        $service->set(
                            'percentage_discount_sum',
                            $billings
                                ->sumOf(
                                    function (Billing $billing) {
                                        return $billing->percentage_discount_sum->toFloat();
                                    },
                                ),
                        );

                        $service->set(
                            'total_sum',
                            $billings
                                ->sumOf(
                                    function (Billing $billing) {
                                        return $billing->total_price->toFloat();
                                    },
                                ),
                        );

                        $service->set(
                            'total_sum_nonbusiness',
                            $billings
                                ->match(['customer.identity_number' => null])
                                ->sumOf(
                                    function (Billing $billing) {
                                        return $billing->total_price->toFloat();
                                    },
                                ),
                        );

                        $service->set(
                            'total_sum_unbilled',
                            $billings
                                ->match(['contract.billed' => false])
                                ->sumOf(
                                    function (Billing $billing) {
                                        return $billing->total_price->toFloat();
                                    },
                                ),
                        );

                        unset($billings);

                        return $service;
                    });

                    // only services that are used
                    $services = $services->filter(function ($service): bool {
                        return $service->number_of_uses > 0;
                    });

                    // sorting by number of uses, if no other sorting is set¨
                    if ($this->getRequest()->getQuery('sort') === null) {
                        return $services->sortBy('number_of_uses');
                    }

                    return $services;
                },
            );

        // filter by service type
        if (is_string($service_type_id) && Validation::uuid($service_type_id)) {
            $servicesQuery->where(['Services.service_type_id' => $service_type_id]);
        }

        // filter by access technology
        if ($access_technology !== null) {
            $servicesQuery->where(['ConnectionProfiles.access_technology' => $access_technology]);
        }

        // Load services with paginator
        $services = $this->paginate($servicesQuery, [
            'sortableFields' => [
                'name',
                'price',
                'ServiceTypes.name',
                'ConnectionProfiles.radius_group',
            ],
            'limit' => PHP_INT_MAX,
            'maxLimit' => PHP_INT_MAX,
        ]);

        $this->set(compact('services', 'month_to_display'));

        $this->setServiceTypesViewVarList();
        $this->setAccessTechnologiesViewVarList();
        $this->setAccessPointsViewVarList();
    }

    /**
     * Overview of Czech customer connection points method
     *
     * What ČTÚ takes by address point, one file for each of its technology categories.
     *
     * @param string|null $category The category whose file is asked for.
     * @return \Cake\Http\Response|null Renders view
     * @throws \Cake\Http\Exception\NotFoundException When the category is not one of ČTÚ's.
     */
    public function overviewOfCzechCustomerConnectionPoints(?string $category = null): ?Response
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        $collector = new ConnectionPointCollector(
            fn(AccessTechnology $technology): ?string => CtuTechnologyCategory::fromTechnology($technology)?->value,
        );
        $points = $collector->collect($month_to_display, 'cz');
        foreach ($collector->problems() as $problem) {
            $this->Flash->warning($problem);
        }

        /** @var array<string, list<\App\RegulatoryReporting\Cz\CtuConnectionPointRow>> $cto_categories */
        $cto_categories = array_map(
            fn(array $byAddress): array => array_map(CtuConnectionPointRow::fromPoint(...), array_values($byAddress)),
            $points,
        );

        if ($this->getRequest()->getParam('_ext') === 'csv' && isset($category)) {
            $csvCategory = CtuTechnologyCategory::tryFrom($category) ?? throw new NotFoundException();

            return $this->response
                ->withStringBody(CtuConnectionPointsCsv::render($csvCategory, $cto_categories[$category] ?? []))
                ->withType('csv')
                ->withDownload($category . '.csv');
        }

        $this->set(compact('cto_categories', 'month_to_display'));

        return null;
    }

    /**
     * Overview of connection speeds method
     *
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfCzechCustomerConnectionSpeeds(): void
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        $cto_categories = $this->applyActiveInMonthScope($this->fetchTable(BillingsTable::class)->find()
            ->contain('Customers')
            ->contain([
                'Contracts' => [
                    'InstallationAddresses',
                ],
            ])
            ->contain([
                'Services' => [
                    'ServiceTypes',
                    'ConnectionProfiles',
                ],
            ]), $month_to_display)
            ->where(['ConnectionProfiles.speed_down IS NOT NULL'])
            ->where(['ConnectionProfiles.speed_up IS NOT NULL'])
            ->where(['ConnectionProfiles.access_technology IN' => self::ctuReportedTechnologies()])
            ->where(['InstallationAddresses.address_registry_reference IS NOT NULL'])
            ->where(['InstallationAddresses.address_registry_source' => 'cz'])

            ->orderBy([
                'ConnectionProfiles.access_technology',
                'InstallationAddresses.city',
            ])

            ->formatResults(
                function (CollectionInterface $billings): CollectionInterface {
                    return $billings
                        ->groupBy(self::ctuCategoryOf(...))
                        ->map(function ($category_billings, $cto_category): CollectionInterface {
                            return (new Collection($category_billings))
                                ->groupBy('contract.installation_address.city')
                                ->map(function ($billings, $city) use ($cto_category): stdClass {
                                    $billings_collection = new Collection($billings);

                                    $address = new stdClass();

                                    $address->billings = $billings_collection;

                                    $address->city = $city;

                                    $address->cto_category = $cto_category;

                                    $address->active_connections = $billings_collection->count();
                                    $address->active_connections_nonbusiness = $billings_collection
                                        ->reject(fn(Billing $billing): bool => $billing->customer->isBusiness())
                                        ->count();

                                    $address->advertised_speeds = new ArrayObject(
                                        $billings_collection
                                            ->countBy(
                                                fn(Billing $billing): string => CtuAdvertisedSpeedBand::fromKbps(
                                                    $billing->service?->connection_profile?->speed_down,
                                                )->value,
                                            )
                                            ->toArray(),
                                        ArrayObject::ARRAY_AS_PROPS,
                                    );

                                    $address->advertised_speeds_nonbusiness = new ArrayObject(
                                        $billings_collection
                                            ->countBy(function (Billing $billing): string {
                                                // skip business customers
                                                if ($billing->customer->isBusiness()) {
                                                    return 'business';
                                                }

                                                return CtuAdvertisedSpeedBand::fromKbps(
                                                    $billing->service?->connection_profile?->speed_down,
                                                )->value;
                                            })
                                            ->toArray(),
                                        ArrayObject::ARRAY_AS_PROPS,
                                    );

                                    return $address;
                                });
                        });
                },
            );

        $this->set(compact('cto_categories', 'month_to_display'));
    }

    /**
     * Overview of dealer commissions
     *
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfDealerCommissions(): void
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        $dealerCommissionsQuery = $this->fetchTable(DealerCommissionsTable::class)->find()
            ->contain('Dealers')
            ->contain('Commissions', function (SelectQuery $q) use ($month_to_display) {
                return $q->contain('Contracts', function (SelectQuery $q) use ($month_to_display) {
                    return $q
                        ->contain('ContractStates')
                        ->contain('Customers')
                        ->contain('Billings', function (SelectQuery $q) use ($month_to_display): SelectQuery {
                            return $this->applyActiveInMonthScope($q->contain('Services'), $month_to_display);
                        })
                        // format results
                        ->formatResults(function (CollectionInterface $contracts): CollectionInterface {
                            return $contracts->map(function (Contract $contract): Contract {
                                $contract->set(
                                    'total_price',
                                    (new Collection($contract->billings))->sumOf(
                                        function (Billing $billing) {
                                            return $billing->total_price->toFloat();
                                        },
                                    ),
                                );

                                return $contract;
                            });
                        });
                })
                // format results
                ->formatResults(function (CollectionInterface $commissions): CollectionInterface {
                    return $commissions->map(function (Commission $commission): Commission {
                        $commission->set(
                            'total_price',
                            (new Collection($commission->contracts))->sumOf('total_price'),
                        );

                        return $commission;
                    });
                });
            });

        $dealers = $dealerCommissionsQuery
            ->all()
            ->groupBy(function ($dealerCommission): string {
                return ($dealerCommission->dealer->name ?? __('unknown dealer'))
                    . ' [ID: ' . $dealerCommission->dealer_id . ']';
            });

        $this->set(compact('dealers', 'month_to_display'));
    }

    /**
     * Restrict a billings query to billings active during the given month.
     *
     * Equivalent to: billing_from <= last day of month AND
     * (billing_until IS NULL OR billing_until >= first day of month).
     *
     * @template TSubject of array|\Cake\Datasource\EntityInterface
     * @param \Cake\ORM\Query\SelectQuery<TSubject> $query Query on a (root or contained) Billings.
     * @param \Cake\I18n\Date $monthToDisplay Any date within the target month.
     * @return \Cake\ORM\Query\SelectQuery<TSubject> The same query (returned for chaining).
     */
    private function applyActiveInMonthScope(SelectQuery $query, Date $monthToDisplay): SelectQuery
    {
        return $query
            ->where(['Billings.billing_from <=' => $monthToDisplay->lastOfMonth()])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $monthToDisplay->firstOfMonth(),
                ],
            ]);
    }

    /**
     * The technologies ČTÚ has a category for, the only ones its reports carry.
     *
     * @return list<string>
     */
    private static function ctuReportedTechnologies(): array
    {
        return array_values(array_map(
            fn(AccessTechnology $technology): string => $technology->value,
            array_filter(
                AccessTechnology::cases(),
                fn(AccessTechnology $technology): bool => CtuTechnologyCategory::fromTechnology($technology) !== null,
            ),
        ));
    }

    /**
     * The ČTÚ category a billing's connection is reported under.
     *
     * The query lets through only what has one, hence the empty string is never really handed back.
     */
    private static function ctuCategoryOf(Billing $billing): string
    {
        $technology = $billing->service?->connection_profile?->access_technology;

        return $technology === null ? '' : (string)CtuTechnologyCategory::fromTechnology($technology)?->value;
    }

    /**
     * Overview of what is wrong with the addresses on record
     *
     * Each check has a tick of its own, so that whoever is working through one of them is
     * not made to load the others. What arrives in the query string decides: a check named
     * there is whatever it was named as, a check absent from it is at its default. That
     * distinction is what makes the ticks work at all - an unticked box sends nothing, and
     * `FormHelper` puts a hidden zero beside each one so that a submitted form says
     * something about every check rather than only the ticked ones.
     *
     * @return void Renders view
     */
    public function overviewOfAddressProblems(): void
    {
        // Read the same way as the ticks below, and on by default: an address says nothing
        // about somebody we no longer serve, and most of what these checks would otherwise
        // report about them is not work anybody is going to do.
        $ignore_inactive = $this->getRequest()->getQuery('ignore_inactive') === null
            || filter_var($this->getRequest()->getQuery('ignore_inactive'), FILTER_VALIDATE_BOOLEAN);

        $registry = new AddressCheckRegistry($ignore_inactive);
        $asked = (array)$this->getRequest()->getQuery('checks', []);

        $shown = [];
        foreach ($registry->all() as $check) {
            $shown[$check->id()] = array_key_exists($check->id(), $asked)
                ? filter_var($asked[$check->id()], FILTER_VALIDATE_BOOLEAN)
                : !$check->optional();
        }

        // A check nobody asked for does not run. Counting them all and drawing some of them
        // would make the ticks cost exactly what they are there to save.
        $results = [];
        foreach ($registry->all() as $check) {
            if ($shown[$check->id()]) {
                $results[$check->id()] = $check->find()->all();
            }
        }

        $checks = $registry->all();

        $this->set(compact('checks', 'shown', 'results', 'ignore_inactive'));
    }

    /**
     * Overview of what is wrong with the contracts on file
     *
     * The ticks work exactly as they do for the addresses above, and for the same reason.
     *
     * @return void Renders view
     */
    public function overviewOfContractProblems(): void
    {
        // Read the same way as the ticks below, and on by default. Off, the checks reach back
        // into contracts nobody is serving any more and into breaks that are long over -
        // which is what putting the history straight needs, and not what daily work is.
        $ignore_inactive = $this->getRequest()->getQuery('ignore_inactive') === null
            || filter_var($this->getRequest()->getQuery('ignore_inactive'), FILTER_VALIDATE_BOOLEAN);

        $registry = new ContractCheckRegistry($ignore_inactive);
        $asked = (array)$this->getRequest()->getQuery('checks', []);

        $shown = [];
        foreach ($registry->all() as $check) {
            $shown[$check->id()] = array_key_exists($check->id(), $asked)
                ? filter_var($asked[$check->id()], FILTER_VALIDATE_BOOLEAN)
                : !$check->optional();
        }

        // A check nobody asked for does not run. Counting them all and drawing some of them
        // would make the ticks cost exactly what they are there to save.
        $results = [];
        foreach ($registry->all() as $check) {
            if ($shown[$check->id()]) {
                $results[$check->id()] = $check->find()->all();
            }
        }

        $checks = $registry->all();

        $this->set(compact('checks', 'shown', 'results', 'ignore_inactive'));
    }

    /**
     * Overview of what is missing from what is on record about the customers
     *
     * The ticks work exactly as they do for the addresses and the contracts above.
     *
     * @return void Renders view
     */
    public function overviewOfCustomerProblems(): void
    {
        // Read the same way as the ticks below, and on by default: what is on file about
        // somebody we no longer serve is not worth chasing.
        $ignore_inactive = $this->getRequest()->getQuery('ignore_inactive') === null
            || filter_var($this->getRequest()->getQuery('ignore_inactive'), FILTER_VALIDATE_BOOLEAN);

        $registry = new CustomerCheckRegistry($ignore_inactive);
        $asked = (array)$this->getRequest()->getQuery('checks', []);

        $shown = [];
        foreach ($registry->all() as $check) {
            $shown[$check->id()] = array_key_exists($check->id(), $asked)
                ? filter_var($asked[$check->id()], FILTER_VALIDATE_BOOLEAN)
                : !$check->optional();
        }

        // A check nobody asked for does not run. Counting them all and drawing some of them
        // would make the ticks cost exactly what they are there to save.
        $results = [];
        foreach ($registry->all() as $check) {
            if ($shown[$check->id()]) {
                $results[$check->id()] = $check->find()->all();
            }
        }

        $checks = $registry->all();

        $this->set(compact('checks', 'shown', 'results', 'ignore_inactive'));
    }
}
