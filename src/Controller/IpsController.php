<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use Cake\I18n\DateTime;
use IPLib\Range\Subnet;

/**
 * Ips Controller
 *
 * @property \App\Model\Table\IpsTable $Ips
 * @method \App\Model\Entity\Ip[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IpsController extends AppController
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
            $conditions += ['Ips.customer_id' => $this->customer_id];
        }
        if (isset($this->contract_id)) {
            $conditions += ['Ips.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Ips.ip::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $ips = $this->paginate($this->Ips->find(
            'all',
            contain: [
                'Contracts',
                'Customers',
            ],
            conditions: $conditions
        ));

        $this->set(compact('ips'));
    }

    /**
     * View method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $ip = $this->Ips->get($id, contain: [
            'Contracts',
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('ip'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $ip = $this->Ips->newEmptyEntity();

        if (isset($this->customer_id)) {
            $ip->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $ip->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The IP address has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $ip->id]);
            }
            $this->Flash->error(__('The IP address could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->Ips->Contracts->find(
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

        $this->set(compact('ip', 'customers', 'contracts'));
    }

    /**
     * Add from range method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function addFromRange()
    {
        $ip = $this->Ips->newEmptyEntity();

        if (isset($this->customer_id)) {
            $ip->customer_id = $this->customer_id;
        }
        if (isset($this->contract_id)) {
            $ip->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->Ips->save($ip)) {
                    $this->Flash->success(__('The IP address has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $ip->id]);
                }
                $this->Flash->error(__('The IP address could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Ips->Customers->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $contracts = $this->Ips->Contracts->find(
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
        if (isset($ip->contract_id)) {
            $contract = $this->Ips->Contracts->get($ip->contract_id);
            if (isset($contract->access_point_id)) {
                $ip_address_ranges_filter['access_point_id'] = $contract->access_point_id;
            }
        }
        switch ($ip->type_of_use ?? $this->Ips->getSchema()->getColumn('type_of_use')['default'] ?? null) {
            case 00:
                $ip_address_ranges_filter['for_customer_addresses_set_via_radius'] = '1';
                break;
            case 10:
                $ip_address_ranges_filter['for_customer_addresses_set_manually'] = '1';
                break;
            case 20:
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
        $ips = [];
        if ($this->getRequest()->getData('ip_address_range') !== null) {
            $ip_address_range = $ip_address_ranges->firstMatch([
                'id' => $this->getRequest()->getData('ip_address_range'),
            ]);

            if ($ip_address_range) {
                // parse range CIDR
                $range = Subnet::parseString($ip_address_range['ip_network']);
                $range_size = $range->getSize();

                // load already used IP addresses
                $used_ips = $this->Ips->find('list')
                    ->where([
                        'Ips.ip >=' => $range->getStartAddress(),
                        'Ips.ip <=' => $range->getEndAddress(),
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
                    if (in_array($ip_from_range->toString(), $used_ips)) {
                        continue 1;
                    }

                    // add IP address for selection
                    $ips[$ip_from_range->toString()] = $ip_from_range->toString();

                    // retrieve previous IP address usage
                    /** @var \App\Model\Entity\RemovedIp|null $previous_ip_address_usage */
                    $previous_ip_address_usage = $this->fetchTable('RemovedIps')
                        ->find()
                        ->contain([
                            'Contracts',
                            'Customers',
                        ])
                        ->where([
                            'RemovedIps.ip' => $ip_from_range->toString(),
                        ])
                        ->orderBy([
                            'RemovedIps.removed' => 'DESC',
                        ])
                        ->first();

                    // add retrieved previous IP address usage to description
                    if ($previous_ip_address_usage) {
                        $ips[$ip_from_range->toString()] .=
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
        $this->set('ips', $ips);

        $this->set(compact('ip', 'customers', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $ip = $this->Ips->get($id);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The IP address has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $ip->id]);
            }
            $this->Flash->error(__('The IP address could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Ips->Contracts->find(
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

        $this->set(compact('ip', 'customers', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $ip = $this->Ips->get($id);

        if ($this->addToRemovedIps($id)) {
            if ($this->Ips->delete($ip)) {
                $this->Flash->success(__('The IP address has been deleted.'));
            } else {
                $this->Flash->error(__('The IP address could not be deleted. Please, try again.'));
            }
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Add IP to removed IPs table (usage before delete)
     *
     * @param string|null $id Ip id.
     * @return bool
     */
    private function addToRemovedIps(?string $id = null)
    {
        $ip = $this->Ips->get($id);

        /** @var \App\Model\Table\RemovedIpsTable $removedIpsTable */
        $removedIpsTable = $this->fetchTable('RemovedIps');

        $removedIp = $removedIpsTable->newEmptyEntity();
        $removedIp = $removedIpsTable->patchEntity($removedIp, $ip->toArray());

        // TODO - add who and when deleted this
        $removedIp->removed = DateTime::now();
        $removedIp->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($removedIpsTable->save($removedIp)) {
            $this->Flash->success(__('The removed IP address has been saved.'));

            return true;
        }

        $this->Flash->error(__('The removed IP address could not be saved. Please, try again.'));

        return false;
    }
}
