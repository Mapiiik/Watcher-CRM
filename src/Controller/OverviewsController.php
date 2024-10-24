<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Model\Entity\Billing;
use App\Model\Entity\Commission;
use App\Model\Entity\Contract;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validation;

/**
 * Overviews Controller
 */
class OverviewsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
    }

    /**
     * Overview of active services method
     *
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfActiveServices()
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));
        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        $cto_category = $this->getRequest()->getQuery('cto_category');
        $access_point_id = $this->getRequest()->getQuery('access_point_id');

        $this->set('show_billings', $this->getRequest()->getQuery('show_billings') == '1');

        $servicesQuery = $this->fetchTable('Services')
            ->find()
            ->contain('Billings', function (SelectQuery $q) use ($month_to_display, $access_point_id) {
                return $q
                    ->contain('Services')
                    ->contain('Customers')
                    ->contain('Contracts', function (SelectQuery $q) use ($access_point_id) {
                        $q->contain('ContractStates');
                        // filter by access point
                        return Validation::uuid($access_point_id) ?
                            $q->where(['Contracts.access_point_id' => $access_point_id]) :
                            $q;
                    })
                    ->where([
                        'Billings.billing_from <=' => $month_to_display->lastOfMonth(), //last day of month
                    ])
                    ->andWhere([
                        'OR' => [
                            'Billings.billing_until IS NULL',
                            'Billings.billing_until >=' => $month_to_display->firstOfMonth(), //first day of month
                        ],
                    ]);
            })
            ->contain('Queues')
            ->contain('ServiceTypes')
            ->formatResults(
                function (CollectionInterface $services) {
                    $services = $services->map(function ($service) {
                        $billings = new Collection($service['billings']);

                        $service['number_of_uses'] = $billings
                            ->sumOf('quantity');

                        $service['number_of_uses_nonbusiness'] = $billings
                            ->match(['customer.ic' => null])
                            ->sumOf('quantity');

                        $service['sum'] = $billings
                            ->sumOf(
                                function (Billing $billing) {
                                    return $billing->sum->toFloat();
                                }
                            );

                        $service['fixed_discount_sum'] = $billings
                            ->sumOf(
                                function (Billing $billing) {
                                    return $billing->fixed_discount_sum->toFloat();
                                }
                            );

                        $service['percentage_discount_sum'] = $billings
                            ->sumOf(
                                function (Billing $billing) {
                                    return $billing->percentage_discount_sum->toFloat();
                                }
                            );

                        $service['total_sum'] = $billings
                            ->sumOf(
                                function (Billing $billing) {
                                    return $billing->total_price->toFloat();
                                }
                            );

                        $service['total_sum_nonbusiness'] = $billings
                            ->match(['customer.ic' => null])
                            ->sumOf(
                                function (Billing $billing) {
                                    return $billing->total_price->toFloat();
                                }
                            );

                        $service['total_sum_unbilled'] = $billings
                            ->match(['contract.billed' => false])
                            ->sumOf(
                                function (Billing $billing) {
                                    return $billing->total_price->toFloat();
                                }
                            );

                        unset($billings);

                        return $service;
                    });

                    // only services that are used
                    $services = $services->filter(function ($service) {
                        return $service->number_of_uses > 0;
                    });

                    // sorting by number of uses, if no other sorting is set¨
                    if ($this->getRequest()->getQuery('sort') === null) {
                        $services = $services->sortBy('number_of_uses');
                    }

                    return $services;
                }
            );

        // filter by service type
        if (Validation::uuid($service_type_id)) {
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

        // load service types
        $this->set(
            'serviceTypes',
            $this->fetchTable('ServiceTypes')->find('list', order: [
                'name',
            ])
        );

        // load CTO categories
        $this->set(
            'ctoCategories',
            $this->fetchTable('Queues')
                ->find(
                    'list',
                    order: 'cto_category',
                    group: 'cto_category',
                    keyField: 'cto_category',
                    valueField: 'cto_category',
                )
                ->whereNotNull('cto_category')
        );

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        if ($accessPoints) {
            $this->set('accessPoints', $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name'));
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);
        }
    }

    /**
     * Overview of connection points method
     *
     * @param string|null $category Optional parameter, CTO category.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfCustomerConnectionPoints(?string $category = null)
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        $cto_categories = $this->fetchTable('Billings')->find()
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
            ])

            ->where([
                'Billings.billing_from <=' => $month_to_display->lastOfMonth(), //last day of month
            ])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $month_to_display->firstOfMonth(), //first day of month
                ],
            ])

            ->where(['Queues.speed_down IS NOT NULL'])
            ->where(['Queues.speed_up IS NOT NULL'])
            ->where(['Queues.cto_category IS NOT NULL'])
            ->where(['InstallationAddresses.ruian_gid IS NOT NULL'])

            ->orderBy([
                'Queues.cto_category',
                'InstallationAddresses.ruian_gid',
            ])

            ->formatResults(
                function (CollectionInterface $billings) {
                    return $billings
                        ->groupBy('service.queue.cto_category')
                        ->map(function ($category_billings, $cto_category) {
                            return (new Collection($category_billings))
                                ->groupBy('contract.installation_address.ruian_gid')
                                ->map(function ($billings, $ruian_gid) use ($cto_category) {
                                    $billings_collection = (new Collection($billings));

                                    $address = new Entity();

                                    $address['billings'] = $billings_collection;

                                    $address['ruian_gid'] = $ruian_gid;

                                    // retrieve full address if RUIAN is connected
                                    try {
                                        /** @var \Ruian\Model\Table\AddressesTable $ruianAddressesTable */
                                        $ruianAddressesTable = $this->fetchTable('Ruian.Addresses');
                                        $address['ruian_address'] = $ruianAddressesTable
                                            ->get($ruian_gid)
                                            ->address;
                                    } catch (MissingConnectionException $missingConnectionError) {
                                        $address['ruian_address'] = null;
                                    } catch (RecordNotFoundException $recordNotFoundError) {
                                        $address['ruian_address'] = null;
                                        $this->Flash->warning(__('Invalid RUIAN GID: {0}', $ruian_gid));
                                    }

                                    $address['cto_category'] = $cto_category;

                                    $address['active_connections'] = $billings_collection->count();
                                    $address['active_connections_nonbusiness'] = $billings_collection
                                        ->match(['customer.ic' => null])
                                        ->count();

                                    $address['active_speeds'] = new Entity(
                                        $billings_collection
                                        ->countBy(function ($billing) {
                                            $commonly_available_download_speed
                                                = $billing->service->queue->speed_down * 0.6;

                                            if ($commonly_available_download_speed < 30720) {
                                                return 'speed_0_30';
                                            } elseif ($commonly_available_download_speed < 102400) {
                                                return 'speed_30_100';
                                            } else {
                                                return 'speed_100_plus';
                                            }
                                        })
                                        ->toArray()
                                    );

                                    $categoryFinder = function ($speed, $cto_category) {
                                        if (in_array($cto_category, ['s2_wifi'])) {
                                            if ($speed >= 1024000) {
                                                return '1000';
                                            } elseif ($speed >= 307200) {
                                                return '300_1000';
                                            } elseif ($speed >= 102400) {
                                                return '100_300';
                                            } else {
                                                return '30_100';
                                            }
                                        } elseif (in_array($cto_category, ['s2_fttb', 's2_ftth'])) {
                                            return '1000';
                                        } else {
                                            return 'unknown';
                                        }
                                    };

                                    $address['available_connections'] = $billings_collection->count();

                                    $maximal_download = $billings_collection
                                        ->max('billing.service.queue.speed_down')
                                        ->service->queue->speed_down;
                                    $effective_download = $maximal_download * 0.6;

                                    $maximal_upload = $billings_collection
                                        ->max('billing.service.queue.speed_up')
                                        ->service->queue->speed_up;
                                    $effective_upload = $maximal_upload * 0.6;

                                    $address['available_speeds'] = new Entity(
                                        [
                                            'maximal_download_category' => $categoryFinder(
                                                $maximal_download,
                                                $cto_category
                                            ),
                                            'effective_download_category' => $categoryFinder(
                                                $effective_download,
                                                $cto_category
                                            ),
                                            'maximal_upload_category' => $categoryFinder(
                                                $maximal_upload,
                                                $cto_category
                                            ),
                                            'effective_upload_category' => $categoryFinder(
                                                $effective_upload,
                                                $cto_category
                                            ),
                                        ]
                                    );

                                    $address['vhcn_category'] = in_array($cto_category, ['s2_fttb', 's2_ftth']) ? 1 : 0;

                                    return $address;
                                });
                        });
                }
            );

        // DOWNLOAD CSV FOR CATEGORY
        if ($this->getRequest()->getParam('_ext') === 'csv' && isset($category)) {
            $csv_data = ''
            . 'AdmIdent;'
            . 'AdmPriloha;'
            . 'Přístupy běžně dostupná rychlost (download) do 30Mb (počet);'
            . 'Přístupy běžně dostupná rychlost (download) 30-100Mb (počet);'
            . 'Přístupy běžně dostupná rychlost (download) nad 100Mb (počet);'
            . 'Přístupy nepodnikajících osob (počet);'
            . 'Disp. přípojky (počet);'
            . 'Disp. přípojky efektivní rychlost download (interval);'
            . 'Disp. přípojky efektivní rychlost upload (interval);'
            . 'Disp. přípojky max. dosažitelná rychlost download (interval);'
            . 'Disp. přípojky max. dosažitelná rychlost upload (interval);'
            . 'VHCN síť (kategorie);'
            . 'Adresa'
            . PHP_EOL;

            foreach ($cto_categories->toArray()[$category] as $connection_point) {
                $csv_data .= ''
                . h($connection_point->ruian_gid) . ';'
                . h($connection_point->cto_category) . ';'
                . (int)$connection_point->active_speeds->speed_0_30 . ';'
                . (int)$connection_point->active_speeds->speed_30_100 . ';'
                . (int)$connection_point->active_speeds->speed_100_plus . ';'
                . (int)$connection_point->active_connections_nonbusiness . ';'
                . (int)$connection_point->available_connections . ';'
                . h($connection_point->available_speeds->effective_download_category) . ';'
                . h($connection_point->available_speeds->effective_upload_category) . ';'
                . h($connection_point->available_speeds->maximal_download_category) . ';'
                . h($connection_point->available_speeds->maximal_upload_category) . ';'
                . (int)$connection_point->vhcn_category . ';'
                . h($connection_point->ruian_address)
                . PHP_EOL;
            }

            return $this->response
                ->withStringBody(iconv('UTF-8', 'CP1250', $csv_data))
                ->withType('csv')
                ->withDownload(
                    $category . '.csv'
                );
        }

        $this->set(compact('cto_categories', 'month_to_display'));
    }

    /**
     * Overview of connection speeds method
     *
     * @param string|null $category Optional parameter, CTO category.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfCustomerConnectionSpeeds(?string $category = null)
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        $cto_categories = $this->fetchTable('Billings')->find()
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
            ])

            ->where([
                'Billings.billing_from <=' => $month_to_display->lastOfMonth(), //last day of month
            ])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $month_to_display->firstOfMonth(), //first day of month
                ],
            ])

            ->where(['Queues.speed_down IS NOT NULL'])
            ->where(['Queues.speed_up IS NOT NULL'])
            ->where(['Queues.cto_category IS NOT NULL'])
            ->where(['InstallationAddresses.ruian_gid IS NOT NULL'])

            ->orderBy([
                'Queues.cto_category',
                'InstallationAddresses.city',
            ])

            ->formatResults(
                function (CollectionInterface $billings) {
                    return $billings
                        ->groupBy('service.queue.cto_category')
                        ->map(function ($category_billings, $cto_category) {
                            return (new Collection($category_billings))
                                ->groupBy('contract.installation_address.city')
                                ->map(function ($billings, $city) use ($cto_category) {
                                    $billings_collection = (new Collection($billings));

                                    $address = new Entity();

                                    $address['billings'] = $billings_collection;

                                    $address['city'] = $city;

                                    $address['cto_category'] = $cto_category;

                                    $address['active_connections'] = $billings_collection->count();
                                    $address['active_connections_nonbusiness'] = $billings_collection
                                        ->match(['customer.ic' => null])
                                        ->count();

                                    $address['advertised_speeds'] = new Entity(
                                        $billings_collection
                                        ->countBy(function ($billing) {
                                            $advertised_download_speed
                                                = $billing->service->queue->speed_down;

                                            if ($advertised_download_speed < 2048) {
                                                return 'speed_0_2';
                                            } elseif ($advertised_download_speed < 10240) {
                                                return 'speed_2_10';
                                            } elseif ($advertised_download_speed < 30720) {
                                                return 'speed_10_30';
                                            } elseif ($advertised_download_speed < 102400) {
                                                return 'speed_30_100';
                                            } elseif ($advertised_download_speed < 1024000) {
                                                return 'speed_100_1000';
                                            } else {
                                                return 'speed_1000_plus';
                                            }
                                        })
                                        ->toArray()
                                    );

                                    $address['advertised_speeds_nonbusiness'] = new Entity(
                                        $billings_collection
                                        ->countBy(function ($billing) {
                                            // skip business customers
                                            if ($billing->customer->ic !== null) {
                                                return 'business';
                                            }

                                            $advertised_download_speed
                                                = $billing->service->queue->speed_down;

                                            if ($advertised_download_speed < 2048) {
                                                return 'speed_0_2';
                                            } elseif ($advertised_download_speed < 10240) {
                                                return 'speed_2_10';
                                            } elseif ($advertised_download_speed < 30720) {
                                                return 'speed_10_30';
                                            } elseif ($advertised_download_speed < 102400) {
                                                return 'speed_30_100';
                                            } elseif ($advertised_download_speed < 1024000) {
                                                return 'speed_100_1000';
                                            } else {
                                                return 'speed_1000_plus';
                                            }
                                        })
                                        ->toArray()
                                    );

                                    return $address;
                                });
                        });
                }
            );

        $this->set(compact('cto_categories', 'month_to_display'));
    }

    /**
     * Overview of dealer commissions
     *
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfDealerCommissions()
    {
        $month_to_display = new Date($this->getRequest()->getQuery('month_to_display', 'now'));

        $dealerCommissionsQuery = $this->fetchTable('DealerCommissions')->find()
            ->contain('Dealers')
            ->contain('Commissions', function (SelectQuery $q) use ($month_to_display) {
                return $q->contain('Contracts', function (SelectQuery $q) use ($month_to_display) {
                    return $q
                        ->contain('ContractStates')
                        ->contain('Customers')
                        ->contain('Billings', function (SelectQuery $q) use ($month_to_display) {
                            return $q
                                ->contain('Services')
                                ->where([
                                    'Billings.billing_from <=' => $month_to_display->lastOfMonth(), //last day of month
                                ])
                                ->andWhere([
                                    'OR' => [
                                        'Billings.billing_until IS NULL',
                                        'Billings.billing_until >=' => $month_to_display->firstOfMonth(), //first day of month
                                    ],
                                ]);
                        })
                        // format results
                        ->formatResults(function (CollectionInterface $contracts) {
                            return $contracts->map(function (Contract $contract) {
                                $contract['total_price'] = (new Collection($contract->billings))->sumOf(
                                    function (Billing $billing) {
                                        return $billing->total_price->toFloat();
                                    }
                                );

                                return $contract;
                            });
                        });
                })
                // format results
                ->formatResults(function (CollectionInterface $commissions) {
                    return $commissions->map(function (Commission $commission) {
                        $commission['total_price'] = (new Collection($commission->contracts))->sumOf('total_price');

                        return $commission;
                    });
                });
            });

        $dealers = $dealerCommissionsQuery
            ->all()
            ->groupBy(function ($dealerCommission) {
                return ($dealerCommission->dealer->name ?? __('unknown dealer'))
                    . ' [ID: ' . $dealerCommission->dealer_id . ']';
            });

        $this->set(compact('dealers', 'month_to_display'));
    }
}
