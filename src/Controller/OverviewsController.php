<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\Check\AddressCheckRegistry;
use App\Addresses\Resolver as AddressesResolver;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Entity\Billing;
use App\Model\Entity\Commission;
use App\Model\Entity\Contract;
use App\Model\Entity\Service;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractStatesTable;
use App\Model\Table\DealerCommissionsTable;
use App\Model\Table\LabelsTable;
use App\Model\Table\ServicesTable;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\ORM\Association;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validation;
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

        // filter by CTO category
        $ctoCategory = $this->getRequest()->getQuery('cto_category');
        if (!empty($ctoCategory)) {
            $filterQuery = $contractsTable->Billings->find()
                ->select([
                    'Billings.contract_id',
                ])
                ->innerJoinWith('Services')
                ->innerJoinWith('Services.Queues')
                ->distinct()
                ->where([
                    'Queues.cto_category' => $ctoCategory,
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
                        'Queues',
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

        // load contract states
        $this->set(
            'contractStates',
            $this->fetchTable(ContractStatesTable::class)->find('list', order: [
                'name',
            ]),
        );

        $this->setServiceTypesViewVarList();
        $this->setCtoCategoriesViewVarList();
        $this->setAccessPointsViewVarList();
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
        $cto_category = $this->getRequest()->getQuery('cto_category');
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
            ->contain('Queues')
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

        // filter by CTO category
        if (!empty($cto_category)) {
            $servicesQuery->where(['Queues.cto_category' => $cto_category]);
        }

        // Load services with paginator
        $services = $this->paginate($servicesQuery, [
            'sortableFields' => [
                'name',
                'price',
                'ServiceTypes.name',
                'Queues.name',
            ],
            'limit' => PHP_INT_MAX,
            'maxLimit' => PHP_INT_MAX,
        ]);

        $this->set(compact('services', 'month_to_display'));

        $this->setServiceTypesViewVarList();
        $this->setCtoCategoriesViewVarList();
        $this->setAccessPointsViewVarList();
    }

    /**
     * Overview of Czech customer connection points method
     *
     * @param string|null $category Optional parameter, CTO category.
     * @return \Cake\Http\Response|null Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfCzechCustomerConnectionPoints(?string $category = null): ?Response
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        /** @var \Cake\Collection\CollectionInterface<string, \Cake\Collection\CollectionInterface<string, \stdClass>> $cto_categories */
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
                    'Queues',
                ],
            ]), $month_to_display)
            ->where(['Queues.speed_down IS NOT NULL'])
            ->where(['Queues.speed_up IS NOT NULL'])
            ->where(['Queues.cto_category IS NOT NULL'])
            ->where(['InstallationAddresses.address_registry_reference IS NOT NULL'])
            ->where(['InstallationAddresses.address_registry_source' => 'cz'])

            ->orderBy([
                'Queues.cto_category',
                'InstallationAddresses.address_registry_reference',
            ])

            ->formatResults(
                function (CollectionInterface $billings): CollectionInterface {
                    // Resolve all installation addresses with registry refs in one batch.
                    // Failure → empty map; groups will fall back to GPS / unknown branches.
                    try {
                        $addressRegistryMatches = AddressesResolver::matchMap(
                            (new Collection($billings))
                                ->extract('contract.installation_address')
                                ->filter()
                                ->toList(),
                        );
                    } catch (RuntimeException $e) {
                        $addressRegistryMatches = [];

                        $this->Flash->error(__(
                            'Could not retrieve addresses from national address registry: {0}',
                            $e->getMessage(),
                        ));
                    }

                    return $billings
                        ->groupBy('service.queue.cto_category')
                        ->map(function (
                            $category_billings,
                            $cto_category,
                        ) use ($addressRegistryMatches): CollectionInterface {
                            return (new Collection($category_billings))
                                ->groupBy(function (Billing $billing): ?string {
                                    $address = $billing->contract->installation_address;

                                    if (
                                        !empty($address->address_registry_reference)
                                            && !empty($address->address_registry_source)
                                    ) {
                                        return $address->address_registry_source
                                            . '|' . $address->address_registry_reference;
                                    }

                                    // This should not happen due to the query conditions, but just in case.
                                    return null;
                                })
                                ->map(function (
                                    $billings,
                                    $key,
                                ) use (
                                    $cto_category,
                                    $addressRegistryMatches,
                                ): stdClass {
                                    $billings_collection = new Collection($billings);

                                    $address = new stdClass();

                                    $address->billings = $billings_collection;

                                    // Attempt to find a match in the address registry results for this address.
                                    $addressRegistryMatch = $addressRegistryMatches[$key] ?? null;

                                    if ($addressRegistryMatch !== null) {
                                        // Authoritative data from the national address registry.
                                        $address->ruian_gid = $addressRegistryMatch['registry_ref'];
                                        $address->ruian_address = $addressRegistryMatch['formatted_address'];
                                    } elseif ($addressRegistryMatches !== []) {
                                        $address->ruian_gid = null;
                                        $address->ruian_address = null;

                                        /** @var array<int, string> $contractsWithInvalidRuianGid */
                                        $contractsWithInvalidRuianGid = $billings_collection
                                            ->extract('contract.number')
                                            ->toList();

                                        $this->Flash->warning(__(
                                            'Invalid RUIAN GID: {0} for addresses associated with contracts: {1}',
                                            explode('|', $key, limit: 2)[1] ?? __('unknown'),
                                            implode(', ', $contractsWithInvalidRuianGid),
                                        ));
                                    } else {
                                        // The registry said nothing at all - the reference the
                                        // address carries is all there is to show, and there is no
                                        // address text to go with it.
                                        $address->ruian_gid = explode('|', $key, limit: 2)[1] ?? null;
                                        $address->ruian_address = null;
                                    }

                                    $address->cto_category = $cto_category;

                                    $address->active_connections = $billings_collection->count();
                                    $address->active_connections_nonbusiness = $billings_collection
                                        ->match(['customer.identity_number' => null])
                                        ->count();

                                    $address->active_speeds = new ArrayObject(
                                        $billings_collection
                                            ->countBy(function (Billing $billing): string {
                                                $commonly_available_download_speed =
                                                    $billing->service?->queue->speed_down ?
                                                        $billing->service->queue->speed_down * 0.6 : null;
                                                if ($commonly_available_download_speed < 30720) {
                                                    return 'speed_0_30';
                                                }

                                                if ($commonly_available_download_speed < 102400) {
                                                    return 'speed_30_100';
                                                }

                                                return 'speed_100_plus';
                                            })
                                            ->toArray(),
                                        ArrayObject::ARRAY_AS_PROPS,
                                    );

                                    $address->available_connections = $billings_collection->count();

                                    $maximal_download = $billings_collection
                                        ->max('billing.service.queue.speed_down')
                                        ->service->queue->speed_down;
                                    $effective_download = $maximal_download * 0.6;

                                    $maximal_upload = $billings_collection
                                        ->max('billing.service.queue.speed_up')
                                        ->service->queue->speed_up;
                                    $effective_upload = $maximal_upload * 0.6;

                                    $address->available_speeds = new ArrayObject(
                                        [
                                            'maximal_download_category' =>
                                                $this->categorizeAvailableSpeed($maximal_download, $cto_category),
                                            'effective_download_category' =>
                                                $this->categorizeAvailableSpeed($effective_download, $cto_category),
                                            'maximal_upload_category' =>
                                                $this->categorizeAvailableSpeed($maximal_upload, $cto_category),
                                            'effective_upload_category' =>
                                                $this->categorizeAvailableSpeed($effective_upload, $cto_category),
                                        ],
                                        ArrayObject::ARRAY_AS_PROPS,
                                    );

                                    $address->vhcn_category = in_array($cto_category, ['s2_fttb', 's2_ftth']) ? 1 : 0;

                                    return $address;
                                });
                        });
                },
            );

        // DOWNLOAD CSV FOR CATEGORY
        if ($this->getRequest()->getParam('_ext') === 'csv' && isset($category)) {
            $headers = [
                'Adresní místo (kód RÚIAN)',
                'Technologická kategorie (identifikátor přílohy)',
                'Přístupy (aktivní přípojky) (počet)',
                'Přístupy (aktivní přípojky) nepodnikajících osob (počet)',
                'Pokryté adresní místo (disponibilní přípojkou) (ANO/NE)',
                'Efektivní rychlost download (interval)',
                'Efektivní rychlost upload (interval)',
                'Maximální dosažitelná rychlost download (interval)',
                'Maximální dosažitelná rychlost upload (interval)',
                'VHCN síť (třída)',
            ];

            if ($category === 's2_catv') {
                $headers[] = 'Standard DOCSIS 3.1 a vyšší (ANO/NE)';
            }

            $headers[] = 'Adresa';
            $csv_data = implode(';', $headers) . PHP_EOL;
            unset($headers);

            foreach ($cto_categories->toArray()[$category] as $connection_point) {
                $row = [
                    h($connection_point->ruian_gid),
                    h($connection_point->cto_category),
                    (int)$connection_point->active_connections,
                    (int)$connection_point->active_connections_nonbusiness,
                    (int)$connection_point->available_connections > 0 ? 'ANO' : 'NE',
                    h($connection_point->available_speeds->effective_download_category),
                    h($connection_point->available_speeds->effective_upload_category),
                    h($connection_point->available_speeds->maximal_download_category),
                    h($connection_point->available_speeds->maximal_upload_category),
                    (int)$connection_point->vhcn_category,
                ];

                if ($category === 's2_catv') {
                    $row[] = 'NE';
                }

                $row[] = h($connection_point->ruian_address);

                $csv_data .= implode(';', $row) . PHP_EOL;
            }

            $csv_data_cp1250 = iconv('UTF-8', 'CP1250', $csv_data);
            if ($csv_data_cp1250 === false) {
                throw new RuntimeException('Unable to convert CSV data from UTF-8 encoding to CP1250 encoding.');
            }

            return $this->response
                ->withStringBody($csv_data_cp1250)
                ->withType('csv')
                ->withDownload(
                    $category . '.csv',
                );
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
                    'Queues',
                ],
            ]), $month_to_display)
            ->where(['Queues.speed_down IS NOT NULL'])
            ->where(['Queues.speed_up IS NOT NULL'])
            ->where(['Queues.cto_category IS NOT NULL'])
            ->where(['InstallationAddresses.address_registry_reference IS NOT NULL'])
            ->where(['InstallationAddresses.address_registry_source' => 'cz'])

            ->orderBy([
                'Queues.cto_category',
                'InstallationAddresses.city',
            ])

            ->formatResults(
                function (CollectionInterface $billings): CollectionInterface {
                    return $billings
                        ->groupBy('service.queue.cto_category')
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
                                        ->match(['customer.identity_number' => null])
                                        ->count();

                                    $address->advertised_speeds = new ArrayObject(
                                        $billings_collection
                                            ->countBy(
                                                fn(Billing $billing): string => $this->bucketAdvertisedSpeed(
                                                    $billing->service?->queue?->speed_down,
                                                ),
                                            )
                                            ->toArray(),
                                        ArrayObject::ARRAY_AS_PROPS,
                                    );

                                    $address->advertised_speeds_nonbusiness = new ArrayObject(
                                        $billings_collection
                                            ->countBy(function (Billing $billing): string {
                                                // skip business customers
                                                if ($billing->customer->identity_number !== null) {
                                                    return 'business';
                                                }

                                                return $this->bucketAdvertisedSpeed(
                                                    $billing->service?->queue?->speed_down,
                                                );
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
     * Bucket a download/upload speed (kbps) into the CTO availability category code,
     * given the technology family of the access.
     *
     * Used by the connection-points report's available_speeds output.
     */
    private function categorizeAvailableSpeed(int|float|null $speed, string $ctoCategory): string
    {
        if (in_array($ctoCategory, ['s2_fttb', 's2_ftth'], true)) {
            return '1000';
        }

        if ($ctoCategory !== 's2_wifi') {
            return 'unknown';
        }

        return match (true) {
            $speed >= 1024000 => '1000',
            $speed >= 307200 => '300_1000',
            $speed >= 102400 => '100_300',
            default => '30_100',
        };
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
        $registry = new AddressCheckRegistry();
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

        $this->set(compact('checks', 'shown', 'results'));
    }

    /**
     * Bucket an advertised download speed (kbps) into the report band code.
     *
     * Used by the connection-speeds report's advertised_speeds output.
     * Null is treated as the lowest band — preserves the original PHP 8
     * `null < 2048` comparison semantics. The query upstream already filters
     * out null speeds via `Queues.speed_down IS NOT NULL`, so this branch
     * is defensive only.
     */
    private function bucketAdvertisedSpeed(?int $speedKbps): string
    {
        return match (true) {
            $speedKbps === null, $speedKbps < 2048 => 'speed_0_2',
            $speedKbps < 10240 => 'speed_2_10',
            $speedKbps < 30720 => 'speed_10_30',
            $speedKbps < 102400 => 'speed_30_100',
            $speedKbps < 1024000 => 'speed_100_1000',
            default => 'speed_1000_plus',
        };
    }
}
