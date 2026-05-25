<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Addresses\Resolver as AddressesResolver;
use App\Controller\AppController;
use App\Model\Entity\Contract;
use App\Model\Entity\IpAddress;
use App\Model\Table\ContractsTable;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;
use Cake\View\JsonView;
use Override;
use RuntimeException;

/**
 * Customers Controller
 *
 * @property \App\Model\Table\CustomersTable $Customers
 */
class CustomersController extends AppController
{
    /**
     * Returns supported output types
     */
    #[Override]
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $customers = $this->Customers
            ->find('all', contain: [
                'AccountingProfiles',
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
            'IpAddresses' => [
                'Contracts',
            ],
            'IpNetworks' => [
                'Contracts',
            ],
            'Logins',
            'Phones',
            'RemovedIpAddresses' => [
                'Contracts',
            ],
            'RemovedIpNetworks' => [
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
            'AccountingProfiles',
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
        $customerPoints = $this->fetchTable(ContractsTable::class)->find()
            ->contain('InstallationAddresses')
            ->contain('Customers')
            ->contain('IpAddresses')
            ->innerJoinWith('ContractStates', function (SelectQuery $q) {
                return $q->where(['ContractStates.active_services' => true]);
            })
            ->formatResults(function (CollectionInterface $customerPoints) {
                // Materialize once so we can iterate twice (matchMap + groupBy).
                $contracts = $customerPoints->toList();

                // Resolve all installation addresses with registry refs in one batch.
                // Failure → empty map; groups will fall back to GPS / unknown branches.
                try {
                    $addressRegistryMatches = AddressesResolver::matchMap(
                        (new Collection($contracts))
                            ->extract('installation_address')
                            ->filter()
                            ->toList(),
                    );
                } catch (RuntimeException $e) {
                    throw new RuntimeException(
                        __(
                            'Could not retrieve addresses from national address registry: {0}',
                            $e->getMessage(),
                        ),
                        previous: $e,
                    );
                }

                return (new Collection($contracts))
                    ->groupBy(function (Contract $contract) {
                        $address = $contract->installation_address;

                        if (
                            !empty($address->address_registry_reference)
                                && !empty($address->address_registry_source)
                        ) {
                            return $address->address_registry_source
                                . '|' . $address->address_registry_reference;
                        }
                        if (!empty($address->gps_x) && !empty($address->gps_y)) {
                            return 'GPS: ' . $address->gps_y . 'N, ' . $address->gps_x . 'E';
                        }

                        return 'unknown location';
                    })
                    ->map(function ($contracts, $key) use ($addressRegistryMatches) {
                        $addressRegistryMatch = $addressRegistryMatches[$key] ?? null;

                        if ($addressRegistryMatch !== null) {
                            // Authoritative data from the national address registry.
                            return [
                                'name' => $addressRegistryMatch['formatted_address'],
                                'gps_y' => $addressRegistryMatch['geometry']['coordinates'][1],
                                'gps_x' => $addressRegistryMatch['geometry']['coordinates'][0],
                                'note' => sprintf(
                                    '%s: %s',
                                    strtoupper($addressRegistryMatch['source']),
                                    $addressRegistryMatch['registry_ref'],
                                ),
                                'CustomerConnections' => $this->buildCustomerConnections($contracts),
                            ];
                        }

                        // GPS-only group, or unknown — use whatever the first contract carries.
                        $first = $contracts[0]->installation_address ?? null;

                        return [
                            'name' => (string)$key,
                            'gps_y' => $first->gps_y ?? null,
                            'gps_x' => $first->gps_x ?? null,
                            'note' => (string)$key,
                            'CustomerConnections' => $this->buildCustomerConnections($contracts),
                        ];
                    })
                    ->toList();
            });

        $this->set('customerPoints', $customerPoints);
        $this->viewBuilder()->setOption('serialize', 'customerPoints');
    }

    /**
     * Builds customer connections data for given contracts.
     *
     * @param array<int, \App\Model\Entity\Contract> $contracts
     * @return \Cake\Collection\CollectionInterface<int, array<string, mixed>>
     */
    private function buildCustomerConnections(array $contracts): CollectionInterface
    {
        /** @var \Cake\Collection\CollectionInterface<int, array<string, mixed>> */
        return (new Collection($contracts))->map(
            fn(Contract $contract) => [
                'name' => $contract->installation_address->name ?? $contract->customer->name,
                'customer_number' => $contract->customer->number,
                'customer_url' => Router::url([
                    'prefix' => false,
                    'controller' => 'Customers',
                    'action' => 'view',
                    $contract->customer->id,
                ]),
                'contract_number' => $contract->number,
                'contract_url' => Router::url([
                    'prefix' => false,
                    'controller' => 'Contracts',
                    'action' => 'view',
                    $contract->id,
                    'customer_id' => $contract->customer_id,
                ]),
                'access_point_id' => $contract->access_point_id,
                'note' => $contract->note,
                'CustomerConnectionIps' => (new Collection($contract->ip_addresses))->map(
                    fn(IpAddress $ipAddress) => [
                        'ip_address' => $ipAddress->ip_address,
                        'name' => $ipAddress->note,
                        'note' => $ipAddress->note,
                    ],
                ),
            ],
        );
    }
}
