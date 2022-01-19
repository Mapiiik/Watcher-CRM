<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenDate;
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
     * View method
     *
     * @param string|null $param Optional parameter.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function overviewOfActiveServices($param = null)
    {
        $invoiced_month = new FrozenDate();

        $servicesQuery = $this->fetchTable('Services')->find()
            ->contain('ServiceTypes')
            ->contain('Billings', function (Query $q) use ($invoiced_month) {
                return $q
                        ->where(['Billings.active' => true])
                        ->andWhere([
                            'OR' => [
                                'Billings.billing_from IS NULL',
                                'Billings.billing_from <=' => $invoiced_month->lastOfMonth(), //last day of month
                            ],
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
}
