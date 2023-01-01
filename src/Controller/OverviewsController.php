<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Model\Entity\Commission;
use App\Model\Entity\Contract;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * Overviews Controller
 *
 * @method \App\Model\Entity\Overview[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $month_to_display = new FrozenDate($this->getRequest()->getQuery('month_to_display'));
        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        $access_point_id = $this->getRequest()->getQuery('access_point_id');

        $servicesQuery = $this->fetchTable('Services')->find()
            ->contain('ServiceTypes')
            ->contain('Billings', function (Query $q) use ($month_to_display, $access_point_id) {
                return $q
                    ->contain('Services')
                    ->contain('Customers')
                    ->contain('Contracts', function (Query $q) use ($access_point_id) {
                        $q->contain('ContractStates');
                        // filter by access point
                        return !empty($access_point_id) ?
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
            ->formatResults(
                function (CollectionInterface $services) {
                    return $services->map(function ($service) {
                        $service['number_of_uses'] = (new Collection($service['billings']))
                            ->sumOf('quantity');

                        $service['number_of_uses_nonbusiness'] = (new Collection($service['billings']))
                            ->match(['customer.ic' => null])->sumOf('quantity');

                        $service['sum'] = (new Collection($service['billings']))->sumOf('sum');

                        $service['fixed_discount_sum'] = (new Collection($service['billings']))
                            ->sumOf('fixed_discount_sum');

                        $service['percentage_discount_sum'] = (new Collection($service['billings']))
                            ->sumOf('percentage_discount_sum');

                        $service['total_sum'] = (new Collection($service['billings']))->sumOf('total_price');

                        $service['total_sum_nonbusiness'] = (new Collection($service['billings']))
                            ->match(['customer.ic' => null])->sumOf('total_price');

                        $service['total_sum_unbilled'] = (new Collection($service['billings']))
                            ->match(['contract.billed' => false])
                            ->sumOf('total_price');

                        return $service;
                    });
                }
            );

        // filter by service type
        if (!empty($service_type_id)) {
            $servicesQuery->where(['service_type_id' => $service_type_id]);
        }

        // load services
        $services = $servicesQuery
            ->find('all')
            ->all()
            ->filter(function ($service) {
                return $service->number_of_uses > 0;
            })
            ->sortBy('number_of_uses');

        $this->set(compact('services', 'month_to_display'));

        // load service types
        $this->set('serviceTypes', $this->fetchTable('ServiceTypes')->find('list', ['order' => 'name']));

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
    public function overviewOfCustomerConnectionPoints($category = null)
    {
        $month_to_display = new FrozenDate($this->getRequest()->getQuery('month_to_display'));

        $cto_categories = $this->fetchTable('Billings')->find()
            ->where([
                'Billings.billing_from <=' => $month_to_display->lastOfMonth(), //last day of month
            ])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $month_to_display->firstOfMonth(), //first day of month
                ],
            ])

            ->where(['Queues.speed_up IS NOT NULL'])
            ->where(['Queues.speed_down IS NOT NULL'])
            ->where(['Queues.cto_category IS NOT NULL'])
            ->where(['InstallationAddresses.ruian_gid IS NOT NULL'])

            ->order('Queues.cto_category')
            ->order('InstallationAddresses.ruian_gid')

            ->contain('Customers')
            ->contain(['Contracts' => ['InstallationAddresses']])
            ->contain(['Services' => ['ServiceTypes', 'Queues']])
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
                                    $address['ruian_address'] = $this->fetchTable('Ruian.Addresses')
                                        ->get($ruian_gid)
                                        ->address;

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

                                    $address['available_speeds'] = new Entity(
                                        $billings_collection
                                        ->countBy(function ($billing) {
                                            if ($billing->service->queue->cto_category == 's2_wifi') {
                                                if ($billing->service->queue->speed_down >= 1024000) {
                                                    return 'speed_1000_plus';
                                                } elseif ($billing->service->queue->speed_down >= 307200) {
                                                    return 'speed_300_1000';
                                                } elseif ($billing->service->queue->speed_down >= 102400) {
                                                    return 'speed_100_300';
                                                } else {
                                                    return 'speed_30_100';
                                                }
                                            } elseif ($billing->service->queue->cto_category == 's2_fttb') {
                                                if ($billing->service->queue->speed_down >= 1024000) {
                                                    return 'speed_1000_plus';
                                                } else {
                                                    return 'speed_300_1000';
                                                }
                                            } else {
                                                return 'unknown';
                                            }
                                        })
                                        ->toArray()
                                    );

                                    $address['vhcn'] = $cto_category == 's2_fttb' ? true : false;

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
            . 'Přístupy (aktivní přípojky);'
            . 'Přístupy (aktivní přípojky) 30-100Mb;'
            . 'Přístupy (aktivní přípojky) nad 100Mb;'
            . 'Přístupy nepodnikajících osob;'
            . 'Přípojky do 30Mb;'
            . 'Přípojky 30-100Mb;'
            . 'Přípojky 100-300Mb;'
            . 'Přípojky 300Mb - 1Gb;'
            . 'Přípojky nad 1Gb;'
            . 'Síť VHCN;'
            . 'Adresa'
            . PHP_EOL;

            foreach ($cto_categories->toArray()[$category] as $connection_point) {
                $csv_data .= ''
                . $connection_point->ruian_gid . ';'
                . $connection_point->cto_category . ';'
                . (int)$connection_point->active_connections . ';'
                . (int)$connection_point->active_speeds->speed_30_100 . ';'
                . (int)$connection_point->active_speeds->speed_100_plus . ';'
                . (int)$connection_point->active_connections_nonbusiness . ';'
                . (int)$connection_point->available_speeds->speed_0_30 . ';'
                . (int)$connection_point->available_speeds->speed_30_100 . ';'
                . (int)$connection_point->available_speeds->speed_100_300 . ';'
                . (int)$connection_point->available_speeds->speed_300_1000 . ';'
                . (int)$connection_point->available_speeds->speed_1000_plus . ';'
                . ($connection_point->vhcn ? 'ANO' : 'NE') . ';'
                . PHP_EOL;
            }

            return $this->response
                ->withStringBody(iconv('UTF-8', 'CP1250', $csv_data))
                ->withType('csv')
                ->withDownload(
                    $category
                    . '_' . $month_to_display->i18nFormat('yyyy-MM') . '.csv'
                );
        }

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
        $month_to_display = new FrozenDate($this->getRequest()->getQuery('month_to_display'));

        $dealerCommissionsQuery = $this->fetchTable('DealerCommissions')->find()
            ->contain('Dealers')
            ->contain('Commissions', function (Query $q) use ($month_to_display) {
                return $q->contain('Contracts', function (Query $q) use ($month_to_display) {
                    return $q
                        ->contain('ContractStates')
                        ->contain('Customers')
                        ->contain('Billings', function (Query $q) use ($month_to_display) {
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
                        // only contracts with billed states
                        ->where(['ContractStates.billed' => true])
                        // format results
                        ->formatResults(function (CollectionInterface $contracts) {
                            return $contracts->map(function (Contract $contract) {
                                $contract['total_price'] = (new Collection($contract->billings))->sumOf('total_price');

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
