<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Entity\IpAddress;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Table\RemovedIpAddressesTable;
use App\NMS\ApiClient as NMSApiClient;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\Validation\Validation;
use IPLib\Range\Subnet;

/**
 * IpAddresses Controller
 *
 * @property \App\Model\Table\IpAddressesTable $IpAddresses
 */
class IpAddressesController extends AppController
{
    use CommonViewVarListsTrait;

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions += ['IpAddresses.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['IpAddresses.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'IpAddresses.ip_address::character varying ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $ipAddresses = $this->paginate($this->IpAddresses->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('ipAddresses'));
    }

    /**
     * View method
     *
     * @param string|null $id IpAddress id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $ipAddress = $this->IpAddresses->get($id, contain: [
            'Contracts',
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('ipAddress'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $ipAddress = $this->IpAddresses->newEmptyEntity();

        if ($this->customer_id !== null) {
            $ipAddress->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $ipAddress->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ipAddress = $this->IpAddresses->patchEntity(
                $ipAddress,
                $this->dataWithAdditionalParameters($this->IpAddresses, $this->getRequest()->getData()),
            );
            if ($this->IpAddresses->save($ipAddress)) {
                $this->Flash->success(__('The IP address has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $ipAddress->id]);
            }
            $this->Flash->error(__('The IP address could not be saved. Please, try again.'));
        }
        $customers = $this->IpAddresses->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->IpAddresses->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipAddress', 'customers', 'contracts'));

        return null;
    }

    /**
     * Add from range method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function addFromRange(): ?Response
    {
        $ipAddress = $this->IpAddresses->newEmptyEntity();

        if ($this->customer_id !== null) {
            $ipAddress->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $ipAddress->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ipAddress = $this->IpAddresses->patchEntity(
                $ipAddress,
                $this->dataWithAdditionalParameters($this->IpAddresses, $this->getRequest()->getData()),
            );

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->IpAddresses->save($ipAddress)) {
                    $this->Flash->success(__('The IP address has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $ipAddress->id]);
                }
                $this->Flash->error(__('The IP address could not be saved. Please, try again.'));
            }
        }
        $customers = $this->IpAddresses->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->IpAddresses->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        // load IP address ranges from NMS
        $ipAddressRangesFilter = [];
        if (isset($ipAddress->contract_id)) {
            $contract = $this->IpAddresses->Contracts->get(
                $ipAddress->contract_id,
                contain: [
                    'ServiceTypes',
                ],
            );

            if (isset($contract->access_point_id)) {
                $ipAddressRangesFilter['access_point_id'] = $contract->access_point_id;
            }
        }

        $default = (int)($this->IpAddresses->getSchema()->getColumn('type_of_use')['default'] ?? 0);
        switch (
            $ipAddress->type_of_use
            ?? IpAddressTypeOfUse::tryFrom($default)
            ?? null
        ) {
            case IpAddressTypeOfUse::CustomerRADIUS:
                $ipAddressRangesFilter['for_customer_addresses_set_via_radius'] = '1';
                break;
            case IpAddressTypeOfUse::CustomerManually:
                $ipAddressRangesFilter['for_customer_addresses_set_manually'] = '1';
                break;
            case IpAddressTypeOfUse::TechnologyManually:
                $ipAddressRangesFilter['for_technology_addresses_set_manually'] = '1';
                break;
        }
        $ipAddressRanges = NMSApiClient::searchIpAddressRanges($ipAddressRangesFilter);
        unset($ipAddressRangesFilter);

        if ($ipAddressRanges != null) {
            $this->set(
                'ipAddressRanges',
                $ipAddressRanges
                    ->sortBy('name', SORT_ASC, SORT_NATURAL)
                    ->sortBy(
                        fn(array $ipAddressRange): int => $ipAddressRange['access_point_id'] === null ? 1 : 0,
                        SORT_ASC,
                        SORT_NUMERIC,
                    )
                    ->map(function (array $ipAddressRange): array {
                        return [
                            'value' => $ipAddressRange['id'],
                            'text' => $ipAddressRange['name'] . ' (' . $ipAddressRange['ip_network'] . ')',
                            'style' => $ipAddressRange['access_point_id'] === null ? 'font-style: italic;' : '',
                        ];
                    }),
            );
        } else {
            $this->Flash->warning(__('The IP address ranges list could not be loaded. Please, try again.'));
            $this->set('ipAddressRanges', []);
        }

        // load available IP addresses if IP address range is selected
        $ipAddresses = [];
        if (
            $ipAddressRanges != null
            && $this->getRequest()->getData('ip_address_range') !== null
        ) {
            $ipAddressRange = $ipAddressRanges->firstMatch([
                'id' => $this->getRequest()->getData('ip_address_range'),
            ]);

            if ($ipAddressRange) {
                $ipAddresses = $this->loadAvailableIpAddresses($ipAddressRange);
            }
        }

        // reverse order of IP addresses, if required by service type
        if (!empty($contract->service_type->assign_ip_addresses_from_behind)) {
            $this->set('ipAddresses', array_reverse($ipAddresses));
        } else {
            $this->set('ipAddresses', $ipAddresses);
        }

        $this->set(compact('ipAddress', 'customers', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id IP Address id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $ipAddress = $this->IpAddresses->get($id);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $ipAddress = $this->IpAddresses->patchEntity($ipAddress, $this->getRequest()->getData());
            if ($this->IpAddresses->save($ipAddress)) {
                $this->Flash->success(__('The IP address has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $ipAddress->id]);
            }
            $this->Flash->error(__('The IP address could not be saved. Please, try again.'));
        }
        $customers = $this->IpAddresses->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->IpAddresses->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipAddress', 'customers', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id IP Address id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $ipAddress = $this->IpAddresses->get($id);

        if ($this->addToRemovedIpAddresses($ipAddress)) {
            if ($this->IpAddresses->delete($ipAddress)) {
                $this->Flash->success(__('The IP address has been deleted.'));
            } else {
                $this->flashValidationErrors($ipAddress->getErrors());
                $this->Flash->error(__('The IP address could not be deleted. Please, try again.'));
            }
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Add IP to removed IPs table (usage before delete)
     *
     * @param \App\Model\Entity\IpAddress $ipAddress IP Address Entity.
     * @return bool
     */
    private function addToRemovedIpAddresses(IpAddress $ipAddress): bool
    {
        $removedIpAddressesTable = $this->fetchTable(RemovedIpAddressesTable::class);

        $removedIpAddress = $removedIpAddressesTable->newEntity($ipAddress->toArray());

        // remove associated data
        $removedIpAddress->unset('contract');
        $removedIpAddress->unset('customer');

        // add who and when deleted this
        $removedIpAddress->removed = DateTime::now();
        $removedIpAddress->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($removedIpAddressesTable->save($removedIpAddress)) {
            $this->Flash->success(__('The removed IP address has been saved.'));

            return true;
        }

        $this->flashValidationErrors($removedIpAddress->getErrors());
        $this->Flash->error(__('The removed IP address could not be saved. Please, try again.'));

        return false;
    }

    /**
     * Bulk Reassignment
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function bulkReassignment(): void
    {
        $ipAddress = $this->IpAddresses->newEmptyEntity();

        if ($this->getRequest()->is('post')) {
            $ipAddress = $this->IpAddresses->patchEntity($ipAddress, $this->getRequest()->getData());
        }

        // load IP addresses
        $accessPointId = $this->getRequest()->getData('access_point_id');
        if (is_string($accessPointId) && Validation::uuid($accessPointId) && isset($ipAddress->type_of_use)) {
            /** @var iterable<\App\Model\Entity\IpAddress> $ipAddresses */
            $ipAddresses = $this->IpAddresses
                ->find()
                ->contain([
                    'Contracts' => [
                        'InstallationAddresses',
                        'ServiceTypes',
                    ],
                    'Customers',
                ])
                ->orderBy([
                    'Contracts.number',
                ])
                ->where([
                    'Contracts.access_point_id' => $accessPointId,
                    'IpAddresses.type_of_use' => $ipAddress->type_of_use,
                ]);
        } else {
            $ipAddresses = [];
        }

        // load IP address ranges from NMS
        $ipAddressRangesFilter = [];
        if (is_string($accessPointId) && Validation::uuid($accessPointId)) {
            $ipAddressRangesFilter['access_point_id'] = $accessPointId;
        }

        $default = (int)($this->IpAddresses->getSchema()->getColumn('type_of_use')['default'] ?? 0);
        switch (
            $ipAddress->type_of_use
            ?? IpAddressTypeOfUse::tryFrom($default)
            ?? null
        ) {
            case IpAddressTypeOfUse::CustomerRADIUS:
                $ipAddressRangesFilter['for_customer_addresses_set_via_radius'] = '1';
                break;
            case IpAddressTypeOfUse::CustomerManually:
                $ipAddressRangesFilter['for_customer_addresses_set_manually'] = '1';
                break;
            case IpAddressTypeOfUse::TechnologyManually:
                $ipAddressRangesFilter['for_technology_addresses_set_manually'] = '1';
                break;
        }
        $ipAddressRanges = NMSApiClient::searchIpAddressRanges($ipAddressRangesFilter);
        unset($ipAddressRangesFilter);

        if ($ipAddressRanges != null) {
            $this->set(
                'ipAddressRanges',
                $ipAddressRanges->sortBy('name', SORT_ASC, SORT_NATURAL)->combine(
                    'id',
                    function (array $ipAddressRange): string {
                        return $ipAddressRange['name'] . ' (' . $ipAddressRange['ip_network'] . ')';
                    },
                ),
            );
        } else {
            $this->Flash->warning(__('The IP address ranges list could not be loaded. Please, try again.'));
            $this->set('ipAddressRanges', []);
        }

        // bulk reassignment
        if ($this->getRequest()->is('post')) {
            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                // load available IP addresses if IP address range is selected
                if (
                    $ipAddressRanges != null
                    && $this->getRequest()->getData('ip_address_range') !== null
                ) {
                    $ipAddressRange = $ipAddressRanges->firstMatch([
                        'id' => $this->getRequest()->getData('ip_address_range'),
                    ]);

                    if ($ipAddressRange) {
                        /** @var array<int, string> $availableIpAddresses */
                        $availableIpAddresses = array_keys($this->loadAvailableIpAddresses(
                            $ipAddressRange,
                            (int)Configure::read('IpAddresses.minimumDaysSinceLastUse'),
                        ));

                        foreach ($ipAddresses as $ipAddressToProcess) {
                            // reassign IP address
                            if (
                                $this->getRequest()->getData(
                                    'reassing_ip_address.' . $ipAddressToProcess->id,
                                ) == $ipAddressToProcess->id
                            ) {
                                // take available IP address (reverse order of IP addresses, if required by service type)
                                if (
                                    !empty(
                                        $ipAddressToProcess
                                            ->contract
                                            ->service_type
                                            ->assign_ip_addresses_from_behind
                                    )
                                ) {
                                    $availableIpAddress = array_pop($availableIpAddresses);
                                } else {
                                    $availableIpAddress = array_shift($availableIpAddresses);
                                }

                                if ($availableIpAddress === null) {
                                    $this->Flash->error(__('No available IP addresses for reassignment.'));

                                    break 1;
                                }

                                if ($this->addToRemovedIpAddresses($ipAddressToProcess)) {
                                    if ($this->IpAddresses->delete($ipAddressToProcess)) {
                                        $this->Flash->success(__('The IP address has been deleted.'));

                                        // create a new entity (with data from the original entity)
                                        $newIpAddress = $this->IpAddresses->newEntity($ipAddressToProcess->toArray());

                                        // remove associated data
                                        $newIpAddress->unset('contract');
                                        $newIpAddress->unset('customer');

                                        // assign new IP address
                                        $newIpAddress->ip_address = $availableIpAddress;
                                        $ipAddressToProcess->set('reassigned_ip_address', $availableIpAddress);

                                        // save new IP address entity
                                        if ($this->IpAddresses->save($newIpAddress)) {
                                            $this->Flash->success(__('The IP address has been saved.'));
                                        } else {
                                            $this->flashValidationErrors($newIpAddress->getErrors());
                                            $this->Flash->error(
                                                __('The IP address could not be saved. Please, try again.'),
                                            );
                                        }
                                    } else {
                                        $this->flashValidationErrors($ipAddressToProcess->getErrors());
                                        $this->Flash->error(
                                            __('The IP address could not be deleted. Please, try again.'),
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                $this->Flash->info(__('Processing completed.'));
            }
        }

        $this->set(compact('ipAddress', 'ipAddresses'));

        // load access points from NMS if possible (only active)
        $this->setAccessPointsViewVarList(onlyActive: true);
    }

    /**
     * Load vailable IP Addresses
     *
     * @param array<string, mixed> $ipAddressRange IP address range.
     * @param int $daysUnused Minimum number of days since last use.
     * @return array<string, string> List of available IP addresses.
     */
    public function loadAvailableIpAddresses(array $ipAddressRange, int $daysUnused = 0): array
    {
        $availableIpAddresses = [];

        // parse range CIDR
        $range = Subnet::parseString($ipAddressRange['ip_network']);
        if ($range === null) {
            $this->Flash->error(__('Invalid IP address range CIDR: {0}', $ipAddressRange['ip_network']));

            return [];
        }

        $rangeSize = $range->getSize();

        // load already used IP addresses
        $usedIpAddresses = $this->IpAddresses->find('list')
            ->where([
                'IpAddresses.ip_address >=' => $range->getStartAddress(),
                'IpAddresses.ip_address <=' => $range->getEndAddress(),
            ])
            ->toArray();

        // test all IP addresses in range for availability
        for ($i = (int)Configure::read('IpAddresses.firstAvailableOffset'); $i < (int)$rangeSize - 1; $i++) {
            $ipFromRange = $range->getAddressAtOffset($i);
            // skip invalid IP addresses
            if ($ipFromRange === null) {
                continue;
            }

            // skip IP gateway
            if ($ipAddressRange['ip_gateway'] === $ipFromRange->toString()) {
                continue 1;
            }

            // skip already used IP addresses
            if (in_array($ipFromRange->toString(), $usedIpAddresses)) {
                continue 1;
            }

            // retrieve previous IP address usage
            /** @var \App\Model\Entity\RemovedIpAddress|null $previousIpAddressUsage */
            $previousIpAddressUsage = $this->fetchTable(RemovedIpAddressesTable::class)
                ->find()
                ->contain([
                    'Contracts',
                    'Customers',
                ])
                ->where([
                    'RemovedIpAddresses.ip_address' => $ipFromRange->toString(),
                ])
                ->orderBy([
                    'RemovedIpAddresses.removed' => 'DESC',
                ])
                ->first();

            if ($previousIpAddressUsage !== null && $previousIpAddressUsage->removed !== null) {
                // check minimum number of days since last use
                if ($previousIpAddressUsage->removed->diffInDays() < $daysUnused) {
                    continue 1;
                }

                // add IP address for selection (with the last use of the IP address in the description)
                $availableIpAddresses[$ipFromRange->toString()] =
                    $ipFromRange->toString()
                    . ' ('
                    . __(
                        'last used until {0} by {1}',
                        $previousIpAddressUsage->removed->i18nFormat(),
                        $previousIpAddressUsage->contract->number
                        ?? $previousIpAddressUsage->customer->number
                        ?? __('unknown customer'),
                    )
                    . ')';
            } else {
                // add IP address for selection
                $availableIpAddresses[$ipFromRange->toString()] = $ipFromRange->toString();
            }
            unset($previousIpAddressUsage);
        }

        return $availableIpAddresses;
    }
}
