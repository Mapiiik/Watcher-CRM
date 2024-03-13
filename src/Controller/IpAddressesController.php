<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Model\Enum\IpAddressTypeOfUse;
use Cake\I18n\DateTime;
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
        $ip_address_ranges_filter = [];
        if (isset($ipAddress->contract_id)) {
            $contract = $this->IpAddresses->Contracts->get(
                $ipAddress->contract_id,
                contain: [
                    'ServiceTypes',
                ]
            );

            if (isset($contract->access_point_id)) {
                $ip_address_ranges_filter['access_point_id'] = $contract->access_point_id;
            }
        }
        switch (
            $ipAddress->type_of_use
            ?? IpAddressTypeOfUse::tryFrom((int)$this->IpAddresses->getSchema()->getColumn('type_of_use')['default'])
            ?? null
        ) {
            case IpAddressTypeOfUse::CustomerRADIUS:
                $ip_address_ranges_filter['for_customer_addresses_set_via_radius'] = '1';
                break;
            case IpAddressTypeOfUse::CustomerManually:
                $ip_address_ranges_filter['for_customer_addresses_set_manually'] = '1';
                break;
            case IpAddressTypeOfUse::TechnologyManually:
                $ip_address_ranges_filter['for_technology_addresses_set_manually'] = '1';
                break;
        }
        $ip_address_ranges = ApiClient::searchIpAddressRanges($ip_address_ranges_filter);
        unset($ip_address_ranges_filter);

        if ($ip_address_ranges) {
            $this->set(
                'ipAddressRanges',
                $ip_address_ranges->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name')
            );
        } else {
            $this->Flash->warning(__('The IP address ranges list could not be loaded. Please, try again.'));
            $this->set('ipAddressRanges', []);
        }

        // load available IP addresses if IP address range is selected
        $ipAddresses = [];
        if ($this->getRequest()->getData('ip_address_range') !== null) {
            $ip_address_range = $ip_address_ranges->firstMatch([
                'id' => $this->getRequest()->getData('ip_address_range'),
            ]);

            if ($ip_address_range) {
                // parse range CIDR
                $range = Subnet::parseString($ip_address_range['ip_network']);
                $range_size = $range->getSize();

                // load already used IP addresses
                $used_ip_addresses = $this->IpAddresses->find('list')
                    ->where([
                        'IpAddresses.ip_address >=' => $range->getStartAddress(),
                        'IpAddresses.ip_address <=' => $range->getEndAddress(),
                    ])
                    ->toArray();

                // test all IP addresses in range for availability
                for ($i = 1; $i < $range_size - 1; $i++) {
                    $ip_from_range = $range->getAddressAtOffset($i);

                    // skip IP gateway
                    if ($ip_address_range['ip_gateway'] === $ip_from_range->toString()) {
                        continue 1;
                    }

                    // skip already used IP addresses
                    if (in_array($ip_from_range->toString(), $used_ip_addresses)) {
                        continue 1;
                    }

                    // add IP address for selection
                    $ipAddresses[$ip_from_range->toString()] = $ip_from_range->toString();

                    // retrieve previous IP address usage
                    /** @var \App\Model\Entity\RemovedIpAddress|null $previous_ip_address_usage */
                    $previous_ip_address_usage = $this->fetchTable('RemovedIpAddresses')
                        ->find()
                        ->contain([
                            'Contracts',
                            'Customers',
                        ])
                        ->where([
                            'RemovedIpAddresses.ip_address' => $ip_from_range->toString(),
                        ])
                        ->orderBy([
                            'RemovedIpAddresses.removed' => 'DESC',
                        ])
                        ->first();

                    // add retrieved previous IP address usage to description
                    if ($previous_ip_address_usage) {
                        $ipAddresses[$ip_from_range->toString()] .=
                            ' ('
                            . __(
                                'last used until {0} by {1}',
                                $previous_ip_address_usage->removed->i18nFormat(),
                                $previous_ip_address_usage->contract->number
                                ?? $previous_ip_address_usage->customer->number
                                ?? __('unknown customer')
                            )
                            . ')';
                    }
                    unset($previous_ip_address_usage);
                }
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

        if ($this->addToRemovedIpAddresses($id)) {
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
     * @param string|null $id IP Address id.
     * @return bool
     */
    private function addToRemovedIpAddresses(?string $id = null)
    {
        $ipAddress = $this->IpAddresses->get($id);

        /** @var \App\Model\Table\RemovedIpAddressesTable $removedIpAddressesTable */
        $removedIpAddressesTable = $this->fetchTable('RemovedIpAddresses');

        $removedIpAddress = $removedIpAddressesTable->newEmptyEntity();
        $removedIpAddress = $removedIpAddressesTable->patchEntity($removedIpAddress, $ipAddress->toArray());

        // TODO - add who and when deleted this
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
}
