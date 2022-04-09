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
        $customers = $this->Customers->find('all', [
            'contain' => ['Taxes'],
        ])->all();

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
    public function view($id = null)
    {
        $customer = $this->Customers->get($id, [
            'contain' => [
                'Taxes',
                'Addresses' => ['Countries'],
                'Billings' => ['Contracts', 'Services'],
                'BorrowedEquipments' => ['Contracts', 'EquipmentTypes'],
                'Contracts' => ['ServiceTypes', 'InstallationAddresses'],
                'Emails',
                'Ips' => ['Contracts'],
                'LabelCustomers' => ['Labels'],
                'Logins',
                'Phones',
                'RemovedIps' => ['Contracts'],
                'SoldEquipments' => ['Contracts', 'EquipmentTypes'],
                'Tasks' => ['TaskTypes', 'TaskStates', 'Dealers']],
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
        $this->request->allowMethod(['post', 'put']);
        $customer = $this->Customers->newEntity($this->request->getData());
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
    public function edit($id = null)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);
        $customer = $this->Customers->get($id);
        $customer = $this->Customers->patchEntity($customer, $this->request->getData());
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
    public function delete($id = null)
    {
        $this->request->allowMethod(['delete']);
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
            ->where([
                'InstallationAddresses.gps_x IS NOT NULL',
                'InstallationAddresses.gps_y IS NOT NULL',
            ])
            ->formatResults(
                function (CollectionInterface $customerPoints) {
                    return $customerPoints
                        ->groupBy('installation_address.ruian_gid')
                        ->map(
                            function ($customerConnections, $ruian_gid) {
                                // Try to load RUIAN record
                                try {
                                    $address = $this->fetchTable('Ruian.Addresses')->get($ruian_gid, [
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
                                        'gps_y' => null,
                                        'gps_x' => null,
                                    ]);
                                }

                                return [
                                    'name' => $address->address,
                                    'gps_y' => $address->gps_y,
                                    'gps_x' => $address->gps_x,
                                    'note' => 'RUIAN: ' . $ruian_gid,
                                    'CustomerConnections' => (new Collection($customerConnections))->map(
                                        function ($contract) {
                                            return [
                                                'name' => $contract->installation_address->name,
                                                'customer_number' => $contract->customer->number,
                                                'contract_number' => $contract->number,
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
