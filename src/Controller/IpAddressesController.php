<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Model\Entity\IpAddress;
use App\Model\Enum\IpAddressTypeOfUse;
use Cake\I18n\DateTime;
use Cake\Validation\Validation;
use IPLib\Range\Subnet;

/**
 * IpAddresses Controller
 *
 * @property \App\Model\Table\IpAddressesTable $IpAddresses
 * @method \App\Model\Entity\IpAddress[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IpAddressesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions += ['IpAddresses.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['IpAddresses.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'IpAddresses.ip_address::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
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
            conditions: $conditions
        ));

        $this->set(compact('ipAddresses'));
    }

    /**
     * View method
     *
     * @param string|null $id IpAddress id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
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
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $ipAddress = $this->IpAddresses->newEmptyEntity();

        if (isset($this->customer_id)) {
            $ipAddress->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $ipAddress->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ipAddress = $this->IpAddresses->patchEntity($ipAddress, $this->getRequest()->getData());
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

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipAddress', 'customers', 'contracts'));
    }

    /**
     * Add from range method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function addFromRange()
    {
        $ipAddress = $this->IpAddresses->newEmptyEntity();

        if (isset($this->customer_id)) {
            $ipAddress->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $ipAddress->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ipAddress = $this->IpAddresses->patchEntity($ipAddress, $this->getRequest()->getData());

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

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        // load IP address ranges from NMS
        $ipAddressRangesFilter = [];
        if (isset($ipAddress->contract_id)) {
            $contract = $this->IpAddresses->Contracts->get(
                $ipAddress->contract_id,
                contain: [
                    'ServiceTypes',
                ]
            );

            if (isset($contract->access_point_id)) {
                $ipAddressRangesFilter['access_point_id'] = $contract->access_point_id;
            }
        }
        switch (
            $ipAddress->type_of_use
            ?? IpAddressTypeOfUse::tryFrom((int)$this->IpAddresses->getSchema()->getColumn('type_of_use')['default'])
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
        $ipAddressRanges = ApiClient::searchIpAddressRanges($ipAddressRangesFilter);
        unset($ipAddressRangesFilter);

        if ($ipAddressRanges) {
            $this->set(
                'ipAddressRanges',
                $ipAddressRanges->sortBy('name', SORT_ASC, SORT_NATURAL)->combine(
                    'id',
                    function (array $ipAddressRange) {
                        return $ipAddressRange['name'] . ' (' . $ipAddressRange['ip_network'] . ')';
                    }
                )
            );
        } else {
            $this->Flash->warning(__('The IP address ranges list could not be loaded. Please, try again.'));
            $this->set('ipAddressRanges', []);
        }

        // load available IP addresses if IP address range is selected
        $ipAddresses = [];
        if ($this->getRequest()->getData('ip_address_range') !== null) {
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
    }

    /**
     * Edit method
     *
     * @param string|null $id IP Address id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
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

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if (isset($this->contract_id)) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
        }

        $this->set(compact('ipAddress', 'customers', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id IP Address id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
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
    private function addToRemovedIpAddresses(IpAddress $ipAddress)
    {
        /** @var \App\Model\Table\RemovedIpAddressesTable $removedIpAddressesTable */
        $removedIpAddressesTable = $this->fetchTable('RemovedIpAddresses');

        $removedIpAddress = $removedIpAddressesTable->newEntity($ipAddress->toArray());

        // remove associated data
        unset($removedIpAddress['contract']);
        unset($removedIpAddress['customer']);

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
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function bulkReassignment()
    {
        $ipAddress = $this->IpAddresses->newEmptyEntity();

        if ($this->getRequest()->is('post')) {
            $ipAddress = $this->IpAddresses->patchEntity($ipAddress, $this->getRequest()->getData());
        }

        // load IP addresses
        $accessPointId = $this->getRequest()->getData('access_point_id');
        if (Validation::uuid($accessPointId) && isset($ipAddress->type_of_use)) {
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
        if (Validation::uuid($accessPointId)) {
            $ipAddressRangesFilter['access_point_id'] = $accessPointId;
        }
        switch (
            $ipAddress->type_of_use
            ?? IpAddressTypeOfUse::tryFrom((int)$this->IpAddresses->getSchema()->getColumn('type_of_use')['default'])
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
        $ipAddressRanges = ApiClient::searchIpAddressRanges($ipAddressRangesFilter);
        unset($ipAddressRangesFilter);

        if ($ipAddressRanges) {
            $this->set(
                'ipAddressRanges',
                $ipAddressRanges->sortBy('name', SORT_ASC, SORT_NATURAL)->combine(
                    'id',
                    function (array $ipAddressRange) {
                        return $ipAddressRange['name'] . ' (' . $ipAddressRange['ip_network'] . ')';
                    }
                )
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
                if ($this->getRequest()->getData('ip_address_range') !== null) {
                    $ipAddressRange = $ipAddressRanges->firstMatch([
                        'id' => $this->getRequest()->getData('ip_address_range'),
                    ]);

                    if ($ipAddressRange) {
                        $availableIpAddresses = array_keys($this->loadAvailableIpAddresses($ipAddressRange, 365));

                        foreach ($ipAddresses as $ipAddressToProcess) {
                            // reassign IP address
                            if (
                                $this->getRequest()->getData(
                                    'reassing_ip_address.' . $ipAddressToProcess->id
                                ) == $ipAddressToProcess->id
                            ) {
                                if ($this->addToRemovedIpAddresses($ipAddressToProcess)) {
                                    if ($this->IpAddresses->delete($ipAddressToProcess)) {
                                        $this->Flash->success(__('The IP address has been deleted.'));

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

                                        // create a new entity (with data from the original entity)
                                        $newIpAddress = $this->IpAddresses->newEntity($ipAddressToProcess->toArray());

                                        // remove associated data
                                        unset($newIpAddress['contract']);
                                        unset($newIpAddress['customer']);

                                        // assign new IP address
                                        $newIpAddress->ip_address = $availableIpAddress;
                                        $ipAddressToProcess['reassigned_ip_address'] = $availableIpAddress;

                                        // save new IP address entity
                                        if ($this->IpAddresses->save($newIpAddress)) {
                                            $this->Flash->success(__('The IP address has been saved.'));
                                        } else {
                                            $this->flashValidationErrors($newIpAddress->getErrors());
                                            $this->Flash->error(
                                                __('The IP address could not be saved. Please, try again.')
                                            );
                                        }
                                    } else {
                                        $this->flashValidationErrors($ipAddressToProcess->getErrors());
                                        $this->Flash->error(
                                            __('The IP address could not be deleted. Please, try again.')
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
     * Load vailable IP Addresses
     *
     * @param array $ipAddressRange IP address range.
     * @param int $daysUnused Minimum number of days since last use.
     * @return array List of available IP addresses.
     */
    public function loadAvailableIpAddresses(array $ipAddressRange, int $daysUnused = 0): array
    {
        $availableIpAddresses = [];

        // parse range CIDR
        $range = Subnet::parseString($ipAddressRange['ip_network']);
        $rangeSize = $range->getSize();

        // load already used IP addresses
        $usedIpAddresses = $this->IpAddresses->find('list')
            ->where([
                'IpAddresses.ip_address >=' => $range->getStartAddress(),
                'IpAddresses.ip_address <=' => $range->getEndAddress(),
            ])
            ->toArray();

        // test all IP addresses in range for availability
        for ($i = 1; $i < $rangeSize - 1; $i++) {
            $ipFromRange = $range->getAddressAtOffset($i);

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
            $previousIpAddressUsage = $this->fetchTable('RemovedIpAddresses')
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

            if ($previousIpAddressUsage) {
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
                        ?? __('unknown customer')
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
