<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;
use Cake\ORM\Query;

/**
 * Reports Controller
 *
 * @method \App\Model\Entity\Report[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ReportsController extends AppController
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
     * @param string|null $param Optional parameter, month of overview.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfActiveServices($param = null)
    {
        $invoiced_month = new FrozenDate($param);

        $servicesQuery = $this->fetchTable('Services')->find()
            ->contain('ServiceTypes')
            ->contain('Billings', function (Query $q) use ($invoiced_month) {
                return $q
                    ->where([
                        'Billings.billing_from <=' => $invoiced_month->lastOfMonth(), //last day of month
                    ])
                    ->andWhere([
                        'OR' => [
                            'Billings.billing_until IS NULL',
                            'Billings.billing_until >=' => $invoiced_month->firstOfMonth(), //first day of month
                        ],
                    ])
                    ->contain(['Services'])
                    ->contain(['Customers']);
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

                        return $service;
                    });
                }
            );

        $services = $this->paginate($servicesQuery);
        $this->set(compact('services'));
    }

    /**
     * Overview of connection points method
     *
     * @param string|null $param Optional parameter, month of overview.
     * @param string|null $category Optional parameter, CTO category.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfCustomerConnectionPoints($param = null, $category = null)
    {
        $invoiced_month = new FrozenDate($param);

        $cto_categories = $this->fetchTable('Billings')->find()
            ->where([
                'Billings.billing_from <=' => $invoiced_month->lastOfMonth(), //last day of month
            ])
            ->andWhere([
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $invoiced_month->firstOfMonth(), //first day of month
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
                                    $address['cto_category'] = $cto_category;

                                    $address['active_connections'] = $billings_collection->count();
                                    $address['active_connections_nonbusiness'] = $billings_collection
                                        ->match(['customer.ic' => null])
                                        ->count();

                                    $address['active_speeds'] = new Entity(
                                        $billings_collection
                                        ->countBy(function ($billing) {
                                            /*
                                            $commonly_available_download_speed
                                                = $billing->service->queue->speed_down * 0.6;

                                            if ($commonly_available_download_speed < 30720) {
                                                return 'speed_0_30';
                                            } elseif ($commonly_available_download_speed < 102400) {
                                                return 'speed_30_100';
                                            } else {
                                                return 'speed_100_plus';
                                            }
                                            */
                                            if ($billing->service->queue->speed_down < 30720) {
                                                return 'speed_0_30';
                                            } elseif ($billing->service->queue->speed_down < 102400) {
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
                                                return 'speed_30_100';
                                            } elseif ($billing->service->queue->cto_category == 's2_fttb') {
                                                return 'speed_300_1000';
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
        if ($this->request->getParam('_ext') === 'csv' && isset($category)) {
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
                    . '_' . $invoiced_month->i18nFormat('yyyy-MM') . '.csv'
                );
        }

        $this->set(compact('cto_categories', 'invoiced_month'));
    }
}
