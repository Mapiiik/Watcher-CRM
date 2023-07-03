<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Ruian\Model\Entity\Address;

/**
 * Customers Controller
 *
 * @property \App\Model\Table\CustomersTable $Customers
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CustomersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $customers = $this->Customers
            ->find('all', contain: [
                'TaxRates',
            ])
            ->all();

        $this->set('customers', $customers);
        $this->viewBuilder()->setOption('serialize', ['customers']);
    }

    /**
     * View method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $customer = $this->Customers->get($id, contain: [
            'Addresses' => [
                'Countries',
            ],
            'Billings' => [
                'Contracts',
                'Services',
            ],
            'BorrowedEquipments' => [
                'Contracts',
                'EquipmentTypes',
            ],
            'Contracts' => [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            'CustomerLabels' => [
                'Labels',
            ],
            'Emails',
            'Ips' => [
                'Contracts',
            ],
            'Logins',
            'Phones',
            'RemovedIps' => [
                'Contracts',
            ],
            'SoldEquipments' => [
                'Contracts',
                'EquipmentTypes',
            ],
            'Tasks' => [
                'TaskTypes',
                'TaskStates',
                'Dealers',
            ],
            'TaxRates',
        ]);

        $this->set('customer', $customer);
        $this->viewBuilder()->setOption('serialize', ['customer']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->getRequest()->allowMethod(['post', 'put']);
        $customer = $this->Customers->newEntity($this->getRequest()->getData());
        if ($this->Customers->save($customer)) {
            $message = 'Saved';
        } else {
            $message = 'Error';
        }
        $this->set([
            'message' => $message,
            'customer' => $customer,
        ]);
        $this->viewBuilder()->setOption('serialize', ['customer', 'message']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $this->getRequest()->allowMethod(['patch', 'post', 'put']);
        $customer = $this->Customers->get($id);
        $customer = $this->Customers->patchEntity($customer, $this->getRequest()->getData());
        if ($this->Customers->save($customer)) {
            $message = 'Saved';
        } else {
            $message = 'Error';
        }
        $this->set([
            'message' => $message,
            'customer' => $customer,
        ]);
        $this->viewBuilder()->setOption('serialize', ['customer', 'message']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['delete']);
        $customer = $this->Customers->get($id);
        if ($this->Customers->delete($customer)) {
            $message = 'Deleted';
        } else {
            $message = 'Error';
        }
        $this->set('message', $message);
        $this->viewBuilder()->setOption('serialize', ['message']);
    }

    /**
     * Customer points method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function customerPoints()
    {
        $customerPoints = $this->fetchTable('Contracts')->find()
            ->contain('InstallationAddresses')
            ->contain('Customers')
            ->contain('Ips')
            ->formatResults(
                function (CollectionInterface $customerPoints) {
                    return $customerPoints
                        ->groupBy(function ($contract) {
                            if (isset($contract->installation_address->ruian_gid)) {
                                // return RUIAN GID as key if set
                                return $contract->installation_address->ruian_gid;
                            } elseif (
                                isset($contract->installation_address->gps_x)
                                && isset($contract->installation_address->gps_y)
                            ) {
                                // return GPS coordinates as key if set
                                return 'GPS: '
                                    . $contract->installation_address->gps_y
                                    . 'N, '
                                    . $contract->installation_address->gps_x
                                    . 'E';
                            } else {
                                // return 'unknown location' as key for others
                                return 'unknown location';
                            }
                        })
                        ->map(
                            function ($contracts, $key) {
                                if (is_numeric($key)) {
                                    // Try to load RUIAN record if RUIAN GID is set
                                    try {
                                        /** @var \Ruian\Model\Entity\Address $address */
                                        $address = $this->fetchTable('Ruian.Addresses')->get($key, [
                                            'fields' => [
                                                'ulice_nazev',
                                                'typ_so',
                                                'cislo_domovni',
                                                'psc',
                                                'obec_nazev',
                                                'cast_obce_nazev',
                                                'gps_y' => 'ST_Y(geometry)',
                                                'gps_x' => 'ST_X(geometry)',
                                            ],
                                        ]);
                                    } catch (RecordNotFoundException $recordNotFoundError) {
                                        $address = new Address([
                                            'gps_y' => $contracts[0]->installation_address->gps_y ?? null,
                                            'gps_x' => $contracts[0]->installation_address->gps_x ?? null,
                                        ]);
                                    }
                                } else {
                                    $address = new Address([
                                        'gps_y' => $contracts[0]->installation_address->gps_y ?? null,
                                        'gps_x' => $contracts[0]->installation_address->gps_x ?? null,
                                    ]);
                                }

                                return [
                                    'name' => is_numeric($key) ? $address->address : $key,
                                    'gps_y' => $address->gps_y,
                                    'gps_x' => $address->gps_x,
                                    'note' => is_numeric($key) ? 'RUIAN: ' . $key : $key,
                                    'CustomerConnections' => (new Collection($contracts))->map(
                                        function ($contract) {
                                            return [
                                                'name' => $contract->installation_address->name ??
                                                    $contract->customer->name,
                                                'customer_number' => $contract->customer->number,
                                                'contract_number' => $contract->number,
                                                'access_point_id' => $contract->access_point_id,
                                                'note' => $contract->note,
                                                'CustomerConnectionIps' => (new Collection($contract->ips))->map(
                                                    function ($ip) {
                                                        return [
                                                            'ip_address' => $ip->ip,
                                                            'name' => $ip->note,
                                                            'note' => $ip->note,
                                                        ];
                                                    }
                                                ),
                                            ];
                                        }
                                    ),
                                ];
                            }
                        );
                }
            );

        $this->set('customerPoints', $customerPoints);
        $this->viewBuilder()->setOption('serialize', 'customerPoints');
    }
}
