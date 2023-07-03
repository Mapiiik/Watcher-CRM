<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * IpNetworks Controller
 *
 * @property \App\Model\Table\IpNetworksTable $IpNetworks
 * @method \App\Model\Entity\IpNetwork[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IpNetworksController extends AppController
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
            $conditions += ['IpNetworks.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['IpNetworks.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'IpNetworks.ip_network::character varying ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => ['Customers', 'Contracts'],
            'order' => ['id' => 'DESC'],
            'conditions' => $conditions,
        ];
        $ipNetworks = $this->paginate($this->IpNetworks);

        $types_of_use = $this->IpNetworks->types_of_use;

        $this->set(compact('ipNetworks', 'types_of_use'));
    }

    /**
     * View method
     *
     * @param string|null $id Ip Network id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $ipNetwork = $this->IpNetworks->get($id, [
            'contain' => [
                'Customers',
                'Contracts',
                'Creators',
                'Modifiers',
            ],
        ]);

        $types_of_use = $this->IpNetworks->types_of_use;

        $this->set(compact('ipNetwork', 'types_of_use'));
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

        $ipNetwork = $this->IpNetworks->newEmptyEntity();

        if (isset($customer_id)) {
            $ipNetwork->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $ipNetwork->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $ipNetwork = $this->IpNetworks->patchEntity($ipNetwork, $this->getRequest()->getData());
            if ($this->IpNetworks->save($ipNetwork)) {
                $this->Flash->success(__('The IP network has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The IP network could not be saved. Please, try again.'));
        }
        $customers = $this->IpNetworks->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->IpNetworks->Contracts->find('list', [
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

        $types_of_use = $this->IpNetworks->types_of_use;

        $this->set(compact('ipNetwork', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Ip Network id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $ipNetwork = $this->IpNetworks->get($id, [
            'contain' => [],
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $ipNetwork = $this->IpNetworks->patchEntity($ipNetwork, $this->getRequest()->getData());
            if ($this->IpNetworks->save($ipNetwork)) {
                $this->Flash->success(__('The IP network has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The IP network could not be saved. Please, try again.'));
        }
        $customers = $this->IpNetworks->Customers->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $contracts = $this->IpNetworks->Contracts->find('list', [
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

        $types_of_use = $this->IpNetworks->types_of_use;

        $this->set(compact('ipNetwork', 'customers', 'contracts', 'types_of_use'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Ip Network id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $contract_id = $this->getRequest()->getParam('contract_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $ipNetwork = $this->IpNetworks->get($id);

        if ($this->addToRemovedIpNetworks($id)) {
            if ($this->IpNetworks->delete($ipNetwork)) {
                $this->Flash->success(__('The IP network has been deleted.'));
            } else {
                $this->Flash->error(__('The IP network could not be deleted. Please, try again.'));
            }
        }

        if (isset($contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Add IP network to removed IP networks table (usage before delete)
     *
     * @param string|null $id Ip Network id.
     * @return bool
     */
    private function addToRemovedIpNetworks($id = null)
    {
        $ip = $this->IpNetworks->get($id);

        /** @var \App\Model\Table\RemovedIpNetworksTable $removedIpNetworksTable */
        $removedIpNetworksTable = $this->fetchTable('RemovedIpNetworks');

        $removedIpNetwork = $removedIpNetworksTable->newEmptyEntity();
        $removedIpNetwork = $removedIpNetworksTable->patchEntity($removedIpNetwork, $ip->toArray());

        // TODO - add who and when deleted this
        $removedIpNetwork->removed = DateTime::now();
        $removedIpNetwork->removed_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($removedIpNetworksTable->save($removedIpNetwork)) {
            $this->Flash->success(__('The removed IP network has been saved.'));

            return true;
        }

        $this->Flash->error(__('The removed IP network could not be saved. Please, try again.'));

        return false;
    }
}
