<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use Cake\I18n\FrozenTime;
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
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['Ips.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Ips.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Ips.ip::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts'],
            'order' => ['id' => 'DESC'],
            'conditions' => $conditions,
        ];
        $ips = $this->paginate($this->Ips);

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ips', 'types_of_use'));
    }

    /**
     * View method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $ip = $this->Ips->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Creators',
                'Modifiers',
            ],
        ]);

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ip', 'types_of_use'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $ip = $this->Ips->newEmptyEntity();

        if (isset($customer_id)) {
            $ip->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $ip->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->Ips->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ip', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Add from range method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function addFromRange()
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $ip = $this->Ips->newEmptyEntity();

        if (isset($customer_id)) {
            $ip->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $ip->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->Ips->save($ip)) {
                    $this->Flash->success(__('The ip has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The ip could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Ips->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->Ips->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $types_of_use = $this->Ips->types_of_use;

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
                }
            }
        }
        $this->set('ips', $ips);

        $this->set(compact('ip', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $ip = $this->Ips->get($id, [
            'contain' => [],
        ]);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $ip = $this->Ips->patchEntity($ip, $this->getRequest()->getData());
            if ($this->Ips->save($ip)) {
                $this->Flash->success(__('The ip has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The ip could not be saved. Please, try again.'));
        }
        $customers = $this->Ips->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
        $contracts = $this->Ips->Contracts->find('list', [
            'order' => 'Contracts.number',
            'contain' => ['ServiceTypes', 'InstallationAddresses'],
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        $types_of_use = $this->Ips->types_of_use;

        $this->set(compact('ip', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Ip id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $contract_id = $this->getRequest()->getParam('contract_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $ip = $this->Ips->get($id);

        if ($this->addToRemovedIps($id)) {
            if ($this->Ips->delete($ip)) {
                $this->Flash->success(__('The ip has been deleted.'));
            } else {
                $this->Flash->error(__('The ip could not be deleted. Please, try again.'));
            }
        }

        if (isset($contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Add IP to removed IPs table (usage before delete)
     *
     * @param string|null $id Ip id.
     * @return bool
     */
    private function addToRemovedIps($id = null)
    {
        $ip = $this->Ips->get($id);

        /** @var \App\Model\Table\RemovedIpsTable $removedIpsTable */
        $removedIpsTable = $this->fetchTable('RemovedIps');

        $removedIp = $removedIpsTable->newEmptyEntity();
        $removedIp = $removedIpsTable->patchEntity($removedIp, $ip->toArray());

        // TODO - add who and when deleted this
        $removedIp->removed = FrozenTime::now();
        $removedIp->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($removedIpsTable->save($removedIp)) {
            $this->Flash->success(__('The removed ip has been saved.'));

            return true;
        }

        $this->Flash->error(__('The removed ip could not be saved. Please, try again.'));

        return false;
    }
}
